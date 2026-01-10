@extends('web::layouts.grids.12')

@section('title', trans('seat-identity-provider::saml.create_application'))
@section('page_header', trans('seat-identity-provider::saml.create_application'))

@section('full')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Create SAML Application</h3>
    </div>
    <form action="{{ route('seat-identity-provider.saml.applications.store') }}" method="POST">
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
              <label for="entity_id">Entity ID (Issuer) <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('entity_id') is-invalid @enderror" id="entity_id" name="entity_id" value="{{ old('entity_id') }}" required>
              <small class="form-text text-muted">The unique identifier for the service provider (e.g., https://app.example.com/saml/metadata).</small>
            </div>

            <div class="form-group">
              <label for="acs_url">Assertion Consumer Service (ACS) URL <span class="text-danger">*</span></label>
              <input type="url" class="form-control @error('acs_url') is-invalid @enderror" id="acs_url" name="acs_url" value="{{ old('acs_url') }}" required>
              <small class="form-text text-muted">The URL where SAML responses will be sent.</small>
            </div>

            <div class="form-group">
              <label for="slo_url">Single Logout (SLO) URL</label>
              <input type="url" class="form-control @error('slo_url') is-invalid @enderror" id="slo_url" name="slo_url" value="{{ old('slo_url') }}">
              <small class="form-text text-muted">Optional. The URL for single logout requests.</small>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label for="metadata_url">Metadata URL</label>
              <div class="input-group">
                <input type="url" class="form-control @error('metadata_url') is-invalid @enderror" id="metadata_url" name="metadata_url" value="{{ old('metadata_url') }}">
                <div class="input-group-append">
                  <button type="button" class="btn btn-info" id="fetch-metadata-btn">
                    <i class="fas fa-download"></i> Fetch
                  </button>
                </div>
              </div>
              <small class="form-text text-muted">Optional. URL to the service provider's SAML metadata. Click Fetch to auto-fill fields.</small>
            </div>

            <div class="form-group">
              <label for="name_id_format">Name ID Format <span class="text-danger">*</span></label>
              <select class="form-control @error('name_id_format') is-invalid @enderror" id="name_id_format" name="name_id_format" required>
                <option value="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" {{ old('name_id_format', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress') == 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' ? 'selected' : '' }}>Email Address</option>
                <option value="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified" {{ old('name_id_format') == 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified' ? 'selected' : '' }}>Unspecified</option>
                <option value="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent" {{ old('name_id_format') == 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' ? 'selected' : '' }}>Persistent</option>
                <option value="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" {{ old('name_id_format') == 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' ? 'selected' : '' }}>Transient</option>
              </select>
              <small class="form-text text-muted">The format of the Name ID sent in SAML assertions.</small>
            </div>

            <div class="form-group">
              <label for="certificate">Service Provider Certificate</label>
              <textarea class="form-control @error('certificate') is-invalid @enderror" id="certificate" name="certificate" rows="5">{{ old('certificate') }}</textarea>
              <small class="form-text text-muted">Optional. The X.509 certificate from the service provider for signature verification.</small>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
              </div>
              <small class="form-text text-muted">Only active applications can process SAML requests.</small>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <a href="{{ route('seat-identity-provider.saml.applications.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-success float-right">Create Application</button>
      </div>
    </form>
  </div>
@stop

@push('javascript')
<script>
  document.getElementById('fetch-metadata-btn').addEventListener('click', function() {
    const metadataUrl = document.getElementById('metadata_url').value;
    if (!metadataUrl) {
      alert('Please enter a Metadata URL first.');
      return;
    }

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
    btn.disabled = true;

    fetch('{{ route('seat-identity-provider.saml.applications.fetch-metadata') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ url: metadataUrl })
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert('Error: ' + data.error);
        return;
      }

      if (data.entity_id) {
        document.getElementById('entity_id').value = data.entity_id;
      }
      if (data.acs_url) {
        document.getElementById('acs_url').value = data.acs_url;
      }
      if (data.slo_url) {
        document.getElementById('slo_url').value = data.slo_url;
      }
      if (data.certificate) {
        document.getElementById('certificate').value = data.certificate;
      }
      if (data.name_id_format) {
        const select = document.getElementById('name_id_format');
        for (let option of select.options) {
          if (option.value === data.name_id_format) {
            option.selected = true;
            break;
          }
        }
      }
    })
    .catch(error => {
      alert('Failed to fetch metadata: ' + error.message);
    })
    .finally(() => {
      btn.innerHTML = originalHtml;
      btn.disabled = false;
    });
  });
</script>
@endpush
