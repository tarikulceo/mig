@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Store Visits')}}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_visits.create') }}" class="btn btn-primary">
                <span>{{translate('Add New Visit')}}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('All Store Visits') }}</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Store')}}</th>
                    <th>{{translate('Sales Rep')}}</th>
                    <th>{{translate('Visit Date')}}</th>
                    <th>{{translate('Purpose')}}</th>
                    <th>{{translate('Status')}}</th>
                    <th>{{translate('Order Amount')}}</th>
                    <th width="10%">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visits as $key => $visit)
                    <tr>
                        <td>{{ ($key+1) + ($visits->currentPage() - 1)*$visits->perPage() }}</td>
                        <td>
                            <div>
                                <div class="fs-14 fw-600">{{ $visit->retailStore->name ?? 'N/A' }}</div>
                                <div class="fs-12 opacity-60">{{ $visit->retailStore->store_code ?? '' }}</div>
                            </div>
                        </td>
                        <td>
                            @if($visit->salesRepresentative)
                                <div class="fs-13">{{ $visit->salesRepresentative->user->name }}</div>
                                <div class="fs-12 opacity-60">{{ $visit->salesRepresentative->employee_id }}</div>
                            @endif
                        </td>
                        <td>{{ $visit->visit_date->format('M d, Y') }}</td>
                        <td>{{ $visit->purpose ?: 'General visit' }}</td>
                        <td>
                            <span class="badge badge-inline badge-{{ $visit->status_badge }}">{{ ucfirst($visit->visit_status) }}</span>
                        </td>
                        <td>{{ single_price($visit->order_amount) }}</td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('store_visits.show', $visit->id) }}" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('store_visits.edit', $visit->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('store_visits.destroy', $visit->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $visits->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection