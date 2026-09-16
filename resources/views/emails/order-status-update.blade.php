<h2>Hello {{ $customerName }}</h2>

<p>Your order <strong>#{{ $orderId }}</strong> status has been updated.</p>

<p>
New Status: <strong>{{ ucfirst($status) }}</strong>
</p>

<p>
Order Amount: ₹{{ $amount }}
</p>

<p>
Thank you for ordering with us.
</p>