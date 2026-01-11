@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::oidc.edit_application'))
@section('page_header', trans('seat-identity-provider::oidc.edit_application'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ trans('seat-identity-provider::oidc.edit_application') }}: {{ $application->name }}</h3>
    </div>
    <form action="{{ route('seat-identity-provider.oidc.applications.update', $application) }}" method="POST">
      @csrf
      @method('PUT')
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

        @if(session('success'))
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('success') }}
            @if(session('client_secret'))
              <hr>
              <strong>New Client Secret:</strong> <code>{{ session('client_secret') }}</code>
              <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Save this secret now. It will not be shown again.</small>
            @endif
          </div>
        @endif

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Client ID</label>
              <div class="input-group">
                <input type="text" class="form-control" value="{{ $application->client_id }}" readonly>
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $application->client_id }}')">
                    <i class="fas fa-copy"></i>
                  </button>
                </div>
              </div>
              <small class="form-text text-muted">This is the OAuth client ID used in authorization requests.</small>
            </div>

            <div class="form-group">
              <label>Client Secret</label>
              <div class="input-group">
                <input type="password" class="form-control" value="****************************************" readonly>
                <div class="input-group-append">
                  <!-- <form action="{{ route('seat-identity-provider.oidc.applications.regenerate-secret', $application) }}" method="POST" style="display: inline;"> -->
                    <!-- @csrf -->
                    <button type="submit" class="btn btn-warning confirmform" data-seat-action="regenerate the client secret">
                      <i class="fas fa-sync"></i> Regenerate
                    </button>
                  <!-- </form> -->
                </div>
              </div>
              <small class="form-text text-muted">The client secret is only shown once when created or regenerated.</small>
            </div>

            <hr>

            <div class="form-group">
              <label for="name">Application Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $application->name) }}" required>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $application->description) }}</textarea>
            </div>

            <div class="form-group">
              <label for="redirect_uris">Redirect URIs <span class="text-danger">*</span></label>
              <textarea class="form-control @error('redirect_uris') is-invalid @enderror" id="redirect_uris" name="redirect_uris" rows="3" required>{{ old('redirect_uris', implode("\n", $application->redirect_uris)) }}</textarea>
              <small class="form-text text-muted">One URI per line.</small>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $application->is_active) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">Active</label>
              </div>
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
                        {{ in_array($scope, old('allowed_scopes', $application->allowed_scopes)) ? 'checked' : '' }}
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
          <i class="fas fa-save"></i> Update Application
        </button>
      </div>
    </form>
  </div>
@stop
