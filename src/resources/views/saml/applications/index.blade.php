@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::saml.applications'))
@section('page_header', trans('seat-identity-provider::saml.applications'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">SAML Applications</h3>
      @can('seat-identity-provider.security')
        <div class="card-tools">
          <a href="{{ route('seat-identity-provider.saml.applications.create') }}" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Create Application
          </a>
        </div>
      @endcan
    </div>
    <div class="card-body">
      @if($applications->isEmpty())
        <div class="callout callout-info">
          <p>No SAML applications have been configured yet.</p>
        </div>
      @else
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Entity ID</th>
              <th>ACS URL</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($applications as $application)
              <tr>
                <td>{{ $application->name }}</td>
                <td><code>{{ $application->entity_id }}</code></td>
                <td><code>{{ $application->acs_url }}</code></td>
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
                      <a href="{{ route('seat-identity-provider.saml.applications.edit', $application) }}" class="btn btn-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('seat-identity-provider.saml.applications.destroy', $application) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this application?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
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
@stop
