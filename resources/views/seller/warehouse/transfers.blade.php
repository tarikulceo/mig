@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 text-primary">{{ translate('Warehouse Transfer History') }}</h1>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Variant') }}</th>
                        <th>{{ translate('From Warehouse') }}</th>
                        <th>{{ translate('To Warehouse') }}</th>
                        <th>{{ translate('Quantity') }}</th>
                        <th>{{ translate('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ optional($transfer->product)->getTranslation('name') }}</td>
                            <td>{{ optional($transfer->productStock)->variant }}</td>
                            <td>{{ optional($transfer->fromWarehouse)->name }}</td>
                            <td>{{ optional($transfer->toWarehouse)->name }}</td>
                            <td>{{ $transfer->quantity }}</td>
                            <td>{{ ucfirst($transfer->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ translate('No transfers found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">
                {{ $transfers->links() }}
            </div>
        </div>
    </div>
@endsection
