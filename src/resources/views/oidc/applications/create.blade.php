@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::oidc.create_application'))
@section('page_header', trans('seat-identity-provider::oidc.create_application'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ trans('seat-identity-provider::oidc.create_application') }}</h3>
    </div>
    <form action="{{ route('seat-identity-provider.oidc.applications.store') }}" method="POST">
      @csrf
      <div class="card-body">
        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="name">Application Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
              <small class="form-text text-muted">A friendly name to identify this application.</small>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
              <small class="form-text text-muted">Optional description of this application's purpose.</small>
            </div>

            <div class="form-group">
              <label for="redirect_uris">Redirect URIs <span class="text-danger">*</span></label>
              <textarea class="form-control @error('redirect_uris') is-invalid @enderror" id="redirect_uris" name="redirect_uris" rows="3" required placeholder="https://example.com/callback&#10;https://example.com/auth/callback">{{ old('redirect_uris') }}</textarea>
              <small class="form-text text-muted">One URI per line. These are the allowed callback URLs for OAuth authorization.</small>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">Active</label>
              </div>
              <small class="form-text text-muted">Only active applications can process authentication requests.</small>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="skip_consent" name="skip_consent" value="1" {{ old('skip_consent', false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="skip_consent">{{ trans('seat-identity-provider::oidc.skip_consent') }}</label>
              </div>
              <small class="form-text text-muted">{{ trans('seat-identity-provider::oidc.skip_consent_help') }}</small>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>Allowed Scopes <span class="text-danger">*</span></label>
              <div class="card card-outline card-info">
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                  @foreach($availableScopes as $scope)
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input" id="scope_{{ Str::slug($scope) }}" name="allowed_scopes[]" value="{{ $scope }}"
                        {{ in_array($scope, old('allowed_scopes', ['openid', 'profile', 'email'])) ? 'checked' : '' }}
                        {{ $scope === 'openid' ? 'onclick="return false;" checked' : '' }}>
                      <label class="custom-control-label" for="scope_{{ Str::slug($scope) }}">
                        <code>{{ $scope }}</code>
                        <br><small class="text-muted">{{ trans('seat-identity-provider::oidc.scope.' . $scope ) }}</small>
                      </label>
                    </div>
                    @if(!$loop->last)<hr class="my-2">@endif
                  @endforeach
                </div>
              </div>
              <small class="form-text text-muted">The <code>openid</code> scope is always required and cannot be disabled.</small>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <a href="{{ route('seat-identity-provider.oidc.applications.index') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Cancel
        </a>
        <button type="submit" class="btn btn-success float-right">
          <i class="fas fa-save"></i> Create Application
        </button>
      </div>
    </form>
  </div>
@stop
