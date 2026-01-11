@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::oidc.keys'))
@section('page_header', trans('seat-identity-provider::oidc.keys'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ trans('seat-identity-provider::oidc.keys') }}</h3>
      @can('seat-identity-provider.security')
        <div class="card-tools">
          <form action="{{ route('seat-identity-provider.oidc.keys.store') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
              <i class="fas fa-plus"></i> {{ trans('seat-identity-provider::oidc.generate_key') }}
            </button>
          </form>
        </div>
      @endcan
    </div>
    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          {{ session('success') }}
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          @foreach($errors->all() as $error)
            {{ $error }}<br>
          @endforeach
        </div>
      @endif

      <div class="callout callout-warning">
        <i class="fas fa-exclamation-triangle"></i>
        {{ trans('seat-identity-provider::oidc.key_rotation_warning') }}
      </div>

      @if($keypairs->isEmpty())
        <div class="callout callout-info">
          <p>No OIDC keypairs have been generated yet. A keypair will be automatically generated when needed.</p>
        </div>
      @else
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>{{ trans('seat-identity-provider::oidc.key_id') }}</th>
              <th>{{ trans('seat-identity-provider::oidc.algorithm') }}</th>
              <th>Status</th>
              <th>Created</th>
              <th>{{ trans('seat-identity-provider::oidc.expires_at') }}</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($keypairs as $keypair)
              <tr>
                <td><code>{{ $keypair->key_id }}</code></td>
                <td>{{ $keypair->algorithm }}</td>
                <td>
                  @if($keypair->is_active)
                    <span class="badge badge-success">Active</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td>{{ $keypair->created_at->diffForHumans() }}</td>
                <td>
                  @if($keypair->expires_at)
                    {{ $keypair->expires_at->format('Y-m-d H:i') }}
                  @else
                    <span class="text-muted">Never</span>
                  @endif
                </td>
                <td>
                  @can('seat-identity-provider.security')
                    <div class="btn-group btn-group-sm">
                      @if(!$keypair->is_active)
                        <form action="{{ route('seat-identity-provider.oidc.keys.activate', $keypair) }}" method="POST" style="display: inline;">
                          @csrf
                          <button type="submit" class="btn btn-primary btn-sm" title="{{ trans('seat-identity-provider::oidc.activate_key') }}">
                            <i class="fas fa-check"></i>
                          </button>
                        </form>
                        <form action="{{ route('seat-identity-provider.oidc.keys.destroy', $keypair) }}" method="POST" style="display: inline;">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-danger btn-sm confirmdelete" title="{{ trans('seat-identity-provider::oidc.delete_key') }}" data-seat-entity="keypair">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      @else
                        <span class="text-muted">Active key cannot be modified</span>
                      @endif
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
@stop
