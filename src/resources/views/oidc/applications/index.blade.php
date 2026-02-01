@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::oidc.applications'))
@section('page_header', trans('seat-identity-provider::oidc.applications'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ trans('seat-identity-provider::oidc.applications') }}</h3>
      @can('seat-identity-provider.security')
        <div class="card-tools">
          <a href="{{ route('seat-identity-provider.oidc.applications.create') }}" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> {{ trans('seat-identity-provider::oidc.create_application') }}
          </a>
        </div>
      @endcan
    </div>
    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          {{ session('success') }}
          @if(session('client_id'))
            <hr>
            <strong>Client ID:</strong> <code>{{ session('client_id') }}</code>
          @endif
          @if(session('client_secret'))
            <br><strong>Client Secret:</strong> <code>{{ session('client_secret') }}</code>
            <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Save this secret now. It will not be shown again.</small>
          @endif
        </div>
      @endif

      @if($applications->isEmpty())
        <div class="callout callout-info">
          <p>No OIDC applications have been configured yet.</p>
        </div>
      @else
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Client ID</th>
              <th>Scopes</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($applications as $application)
              <tr>
                <td>
                  {{ $application->name }}
                  @if($application->description)
                    <br><small class="text-muted">{{ $application->description }}</small>
                  @endif
                </td>
                <td><code>{{ $application->client_id }}</code></td>
                <td>
                  @foreach($application->allowed_scopes as $scope)
                    <span class="badge badge-info">{{ $scope }}</span>
                  @endforeach
                </td>
                <td>
                  @if($application->is_active)
                    <span class="badge badge-success">Active</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td>
                  @can('seat-identity-provider.security')
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('seat-identity-provider.oidc.applications.edit', $application) }}" class="btn btn-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('seat-identity-provider.oidc.applications.destroy', $application) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm confirmdelete" title="Delete" data-seat-entity="{{ trans('seat-identity-provider::oidc.application') }}">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">OIDC Endpoints</h3>
    </div>
    <div class="card-body">
      <p class="text-muted">Use these endpoints to configure your OIDC client applications.</p>
      <table class="table table-sm">
        <tr>
          <td style="width: 200px;"><strong>Discovery URL</strong></td>
          <td><code>{{ route('seat-identity-provider.oidc.discovery') }}</code></td>
        </tr>
        <tr>
          <td><strong>Authorization Endpoint</strong></td>
          <td><code>{{ route('seat-identity-provider.oauth2.authorize') }}</code></td>
        </tr>
        <tr>
          <td><strong>Token Endpoint</strong></td>
          <td><code>{{ route('seat-identity-provider.oauth2.token') }}</code></td>
        </tr>
        <tr>
          <td><strong>UserInfo Endpoint</strong></td>
          <td><code>{{ route('seat-identity-provider.oidc.userinfo') }}</code></td>
        </tr>
        <tr>
          <td><strong>JWKS URI</strong></td>
          <td><code>{{ route('seat-identity-provider.oidc.jwks') }}</code></td>
        </tr>
      </table>
    </div>
  </div>
@stop
