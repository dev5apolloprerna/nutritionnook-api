@extends('layouts.app')
@section('content')

<style>
    .nn-payout-header { background: linear-gradient(135deg, #FF8C00, #FFA500); border-radius: 0 0 20px 20px; padding: 20px 25px; color: #fff; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .nn-pcard { background: #FFF8F0; border: 1px solid #FFE0B2; border-radius: 14px; padding: 22px; height: 100%; }
    .nn-summary-stat { text-align: center; padding: 14px; background: #fff; border-radius: 10px; border: 1px solid #FFE0B2; }
    .nn-summary-stat .stat-num { font-size: 1.4rem; font-weight: 800; color: #FF8C00; }
    .nn-summary-stat .stat-lbl { font-size: 0.75rem; color: #999; text-transform: uppercase; }
    .nn-chef-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .nn-chef-table th { background: #FFF0E0; padding: 12px; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #FFE0B2; }
    .nn-chef-table td { padding: 12px; border-bottom: 1px solid #FFF0E0; font-size: 0.85rem; }
    .nn-fee-tag { display: block; font-size: 0.7rem; color: #999; text-transform: none; font-weight: normal; margin-top: 4px; }
    .nn-btn-pay-single { color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; }
</style>

<div class="nn-payout-page">
    <div class="nn-payout-header">
        <h2>Payout Management</h2>
        <div class="badge-info">Next Run: {{ $nextScheduledDate->format('M d, Y') }}</div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="nn-summary-stat">
                <div class="stat-num">{{ count($chefSummaries) }}</div>
                <div class="stat-lbl">Eligible Chefs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="nn-summary-stat">
                <div class="stat-num">₹{{ number_format(collect($chefSummaries)->sum('dish_price'), 2) }}</div>
                <div class="stat-lbl">Total Dish Price</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="nn-summary-stat">
                <div class="stat-num">₹{{ number_format(collect($chefSummaries)->sum('security_deposit'), 2) }}</div>
                <div class="stat-lbl">Security Held</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="nn-summary-stat" style="border: 2px solid #28a745;">
                <div class="stat-num" style="color: #28a745;">₹{{ number_format(collect($chefSummaries)->sum('net_payout'), 2) }}</div>
                <div class="stat-lbl">Net Payable</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="nn-pcard">
                <h4 class="mb-4">Chef Payout Summary</h4>
                <div class="table-responsive">
                    <table class="nn-chef-table">
                        <thead>
                            <tr>
                                <th>Chef & Bank Info</th>
                                <th>
                                    Comm. %
                                    <span class="nn-fee-tag">(Standard platform fee)</span>
                                </th>
                                <th>
                                    Dish Price (100%)
                                    <span class="nn-fee-tag">(Base price from menu)</span>
                                </th>
                                <th>
                                    Security (10%)
                                    <span class="nn-fee-tag">(Held for platform security)</span>
                                </th>
                                <th style="background: #eefdf3;">
                                    Net Payout (90%)
                                    <span class="nn-fee-tag">(Total amount to be paid)</span>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chefSummaries as $summary)
                            <tr>
                                <td>
                                    <strong>{{ $summary['name'] }}</strong><br>
                                    <small class="text-muted">A/C: {{ $summary['account_number'] }}</small>
                                </td>
                                <td><span class="badge badge-info">{{ $summary['commission_rate'] }}%</span></td>
                                <td>₹{{ number_format($summary['dish_price'], 2) }}</td>
                                <td style="color: #dc3545;">- ₹{{ number_format($summary['security_deposit'], 2) }}</td>
                                <td style="background: #f9fff9; border-left: 3px solid #28a745;">
                                    <strong style="color: #28a745; font-size: 1.1rem;">
                                        ₹{{ number_format($summary['net_payout'], 2) }}
                                    </strong>
                                </td>
                                <td>
                                    @php
                                        $payoutRecord = \DB::table('payouts')->where('chef_id', $summary['id'])->first();
                                        $isAlreadyPaid = ($payoutRecord && in_array($payoutRecord->status, ['completed', 'paid', 'success']));
                                    @endphp

                                    @if($isAlreadyPaid)
                                        <div style="text-align: center;">
                                            <span class="badge" style="background-color: #28a745; color: white; padding: 8px 15px; border-radius: 5px; font-weight: bold;">
                                                <i class="fa fa-check-circle"></i> PAID
                                            </span>
                                        </div>
                                    @elseif($summary['has_bank_details'])
                                        <button type="button" class="nn-btn-pay-single"
                                                style="background-color: #007bff;"
                                                data-toggle="modal" data-target="#payoutModal{{ $summary['id'] }}">
                                            Mark Paid
                                        </button>
                                    @else
                                        <span class="badge badge-danger">Missing Info</span>
                                    @endif
                                </td>
                            </tr>

                            <div class="modal fade" id="payoutModal{{ $summary['id'] }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-md" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" style="color: #333;">Complete Payout - {{ $summary['name'] }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('admin.payouts.mark-paid', $summary['id']) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label style="font-weight: bold;">Payout Amount (₹)</label>
                                                        <input type="number" step="0.01" min="0" name="payout_amount" class="form-control" value="{{ number_format($summary['net_payout'], 2, '.', '') }}">
                                                        <small class="text-muted">Computed net payout — edit if the amount actually paid differs.</small>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label style="color: #666; font-size: 13px;">Chef Name</label>
                                                        <input type="text" class="form-control" value="{{ $summary['name'] }}" readonly style="background: #f8f9fa;">
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label style="color: #666; font-size: 13px;">Bank Account Number</label>
                                                        <input type="text" class="form-control" value="{{ $summary['account_number'] ?? 'N/A' }}" readonly style="background: #f8f9fa;">
                                                    </div>

                                                    <div class="col-12"><hr></div>

                                                    <div class="col-md-12 mb-3">
                                                        <label style="font-weight: bold;">Transfer Method <span class="text-danger">*</span></label>
                                                        <select name="payment_method" class="form-control" required>
                                                            <option value="">-- Select Method --</option>
                                                            <option value="UPI">UPI</option>
                                                            <option value="Bank Transfer">Bank Transfer (IMPS/NEFT)</option>
                                                            <option value="Net Banking">Net Banking</option>
                                                            <option value="Cheque">Cheque</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-12 mb-3">
                                                        <label style="font-weight: bold;">Transaction ID / Ref. No <span class="text-danger">*</span></label>
                                                        <input type="text" name="transaction_id" class="form-control" placeholder="Enter Reference Number" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Confirm & Mark Paid</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection