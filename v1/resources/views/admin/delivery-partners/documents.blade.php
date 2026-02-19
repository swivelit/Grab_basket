@extends('admin.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Verification Documents - {{ $deliveryPartner->name }}</h3>
                    <a href="{{ route('admin.delivery-partners.show', $deliveryPartner) }}" class="btn btn-secondary float-right">
                        Back to Details
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($deliveryPartner->id_proof)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title m-0">ID Proof</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ Storage::disk('public')->url($deliveryPartner->id_proof) }}" target="_blank">
                                        <img src="{{ Storage::disk('public')->url($deliveryPartner->id_proof) }}" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($deliveryPartner->address_proof)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title m-0">Address Proof</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ Storage::disk('public')->url($deliveryPartner->address_proof) }}" target="_blank">
                                        <img src="{{ Storage::disk('public')->url($deliveryPartner->address_proof) }}" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($deliveryPartner->vehicle_rc)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title m-0">Vehicle RC</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ Storage::disk('public')->url($deliveryPartner->vehicle_rc) }}" target="_blank">
                                        <img src="{{ Storage::disk('public')->url($deliveryPartner->vehicle_rc) }}" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($deliveryPartner->driving_license)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title m-0">Driving License</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ Storage::disk('public')->url($deliveryPartner->driving_license) }}" target="_blank">
                                        <img src="{{ Storage::disk('public')->url($deliveryPartner->driving_license) }}" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($deliveryPartner->status === 'pending')
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Action Required</h5>
                                    <p>Please verify all documents and take appropriate action:</p>
                                    <form action="{{ route('admin.delivery-partners.status', $deliveryPartner) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-success">
                                            Approve Partner
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.delivery-partners.status', $deliveryPartner) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-danger">
                                            Reject Partner
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection