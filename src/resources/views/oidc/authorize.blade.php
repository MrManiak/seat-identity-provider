@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::oidc.authorize_application'))
@section('page_header', trans('seat-identity-provider::oidc.authorize_application'))

@section('full')
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-key"></i>
            {{ trans('seat-identity-provider::oidc.authorization_request') }}
          </h3>
        </div>
        <div class="card-body">
          <div class="text-center mb-4">
            <h4>{{ $client->name }}</h4>
            @if($client->description)
              <p class="text-muted">{{ $client->description }}</p>
            @endif
          </div>

          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            {{ trans('seat-identity-provider::oidc.authorization_prompt') }}
          </div>

          @if(count($scopes) > 0)
            <div class="mb-4">
              <strong>{{ trans('seat-identity-provider::oidc.requested_scopes') }}:</strong>
              <ul class="list-unstyled mt-2">
                @foreach($scopes as $scope)
                  <li class="mb-2">
                    <i class="fas fa-check-circle text-success"></i>
                    <strong>{{ $scope->getIdentifier() }}</strong>
                    <br/>
                    <small class="text-muted ml-4">{{ trans('seat-identity-provider::oidc.scopes.' . $scope->getIdentifier()) }}</small>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('seat-identity-provider.oauth2.approve') }}">
            @csrf
            <div class="d-flex justify-content-between">
              <button type="submit" name="approve" value="0" class="btn btn-danger">
                <i class="fas fa-times"></i> {{ trans('seat-identity-provider::oidc.deny') }}
              </button>
              <button type="submit" name="approve" value="1" class="btn btn-success">
                <i class="fas fa-check"></i> {{ trans('seat-identity-provider::oidc.authorize') }}
              </button>
            </div>
          </form>
        </div>
        <div class="card-footer text-muted text-center">
          <small>
            {{ trans('seat-identity-provider::oidc.logged_in_as') }} <strong>{{ auth()->user()->name }}</strong>
          </small>
        </div>
      </div>
    </div>
  </div>
@endsection
