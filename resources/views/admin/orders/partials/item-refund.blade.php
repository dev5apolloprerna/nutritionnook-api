<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Product-wise Refund</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Select products and enter the refund amount for each. You can refund all or part of a product’s remaining value. Amounts include proportional order discount and GST, excluding platform fees and deposits.</p>
        @if($itemRefundError)
        <p class="text-danger">{{ $itemRefundError }}</p>
        @elseif(!in_array($order->status, ['delivered', 'rejected'], true) || $order->payment_status !== 'received' || !$order->razorpay_payment_id)
        <p class="text-muted">Item refunds are available for paid delivered or rejected orders.</p>
        @else
        <form id="item-refund-form" action="{{ route('orders.refundItems') }}" method="POST">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Select</th>
                            <th scope="col">Product</th>
                            <th scope="col">Qty</th>
                            <th scope="col">Refund</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemRefundLines as $line)
                        <tr>
                            <td><input type="checkbox" class="item-refund-checkbox" name="item_indexes[]" value="{{ $line['index'] }}" data-amount="{{ $line['remaining_paise'] }}" aria-label="Refund {{ $line['name'] }}" @disabled(!$line['available'])></td>
                            <td>{{ $line['name'] }} @if(!$line['available'])<small class="d-block text-muted">Unavailable or refund already pending/completed</small>@endif</td>
                            <td>{{ $line['quantity'] }}</td>
                            <td>
                                <label class="sr-only" for="item-amount-{{ $line['index'] }}">Refund amount for {{ $line['name'] }}</label>
                                <input id="item-amount-{{ $line['index'] }}" type="number" class="form-control item-refund-amount" name="item_amounts[{{ $line['index'] }}]" min="0.01" max="{{ number_format($line['remaining_paise'] / 100, 2, '.', '') }}" step="0.01" value="{{ number_format($line['remaining_paise'] / 100, 2, '.', '') }}" required disabled>
                                <small class="text-muted">Remaining: ₹{{ number_format($line['remaining_paise'] / 100, 2) }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p>Selected refund: <strong id="item-refund-total">₹0.00</strong></p>
            <button id="item-refund-submit" class="btn btn-danger" type="submit" disabled>Refund selected products</button>
            <p id="item-refund-message" class="mt-3" role="status" aria-live="polite"></p>
        </form>
        @endif
        @if($itemRefundHistory->isNotEmpty())
        <h6 class="mt-4">Item refund history</h6>
        <ul class="list-unstyled">
            @foreach($itemRefundHistory as $refund)
            <li class="border-top py-2">#{{ $refund->id }} — {{ collect($refund->selected_items)->pluck('name')->implode(', ') }} — ₹{{ number_format($refund->refund_amount, 2) }} — {{ ucfirst($refund->status) }}</li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('item-refund-form');
        if (!form) return;
        const boxes = Array.from(form.querySelectorAll('.item-refund-checkbox'));
        const button = document.getElementById('item-refund-submit');
        const message = document.getElementById('item-refund-message');
        let submitting = false;
        const selected = () => boxes.filter(box => box.checked && !box.disabled);
        const amountInput = box => document.getElementById('item-amount-' + box.value);
        const total = () => selected().reduce((sum, box) => sum + Math.round(Number(amountInput(box).value) * 100), 0);
        const update = () => {
            boxes.forEach(box => {
                amountInput(box).disabled = submitting || box.disabled || !box.checked;
            });
            document.getElementById('item-refund-total').textContent = '₹' + (total() / 100).toFixed(2);
            button.disabled = submitting || selected().length === 0 || !form.checkValidity();
        };
        form.addEventListener('change', update);
        form.addEventListener('input', update);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (submitting || !selected().length) return;
            if (!window.confirm('Refund the selected products for ₹' + (total() / 100).toFixed(2) + '?')) return;
            if (!form.reportValidity()) return;
            const indexes = selected().map(box => Number(box.value));
            const amounts = Object.fromEntries(selected().map(box => [box.value, amountInput(box).value]));
            submitting = true;
            button.disabled = true;
            message.textContent = 'Processing refund…';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value
                    },
                    body: JSON.stringify({
                        order_id: Number(form.querySelector('[name="order_id"]').value),
                        item_indexes: indexes,
                        item_amounts: amounts
                    })
                });
                const data = await response.json();
                message.textContent = data.message || 'Unable to confirm the refund. Reload to check its status before retrying.';
                if (response.ok && data.success) {
                    window.location.reload();
                } else if (response.status === 422 || response.status === 403) {
                    submitting = false;
                    button.disabled = selected().length === 0;
                }
                // Network/gateway uncertainty leaves submit disabled; server-side
                // reservations prevent a repeat charge even after reloading.
            } catch (error) {
                message.textContent = 'Unable to confirm the refund. Reload and check refund history before retrying.';
            }
        });
    });
</script>