{{-- Record payment modal --}}
<dialog id="payment-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 class="font-display text-xl font-bold text-ink">Record payment</h2>
    <p id="payment-tenant-name" class="mt-1 text-sm text-inkSoft"></p>

    @if ($errors->payment->any())
        <div class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
            @foreach ($errors->payment->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form id="payment-form" method="POST" action="" class="mt-4 flex flex-col gap-4">
        @csrf
        <input type="hidden" name="tenant_id">
        <input type="hidden" name="tenant_name">

        <label class="text-sm font-semibold text-ink">Amount (₱)
            <input type="number" step="0.01" min="0.01" name="amount" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <div class="grid grid-cols-2 gap-4">
            <label class="text-sm font-semibold text-ink">Date paid
                <input type="date" name="transaction_date" required
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-ink">Method
                <select name="payment_method"
                        class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="other">Other</option>
                </select>
            </label>
        </div>

        <label class="text-sm font-semibold text-ink">Notes (optional)
            <textarea name="notes" rows="2" maxlength="500"
                      class="mt-1 w-full rounded-xl border border-ink/10 bg-paper p-3 text-sm font-normal"></textarea>
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
            <button type="submit" class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-tealDeep">Save payment</button>
        </div>
    </form>
</dialog>

<script>
    const paymentUrl = @js(route('admin.billing.payments.store', ['tenant' => '__ID__']));
    const paymentToday = @js(now()->toDateString());

    function openPayment(id, name, prefill = {}) {
        const form = document.getElementById('payment-form');
        form.reset();
        form.action = paymentUrl.replace('__ID__', id);
        form.elements.tenant_id.value = id;
        form.elements.tenant_name.value = name;
        form.elements.amount.value = prefill.amount ?? '';
        form.elements.transaction_date.value = prefill.transaction_date ?? paymentToday;
        form.elements.payment_method.value = prefill.payment_method ?? 'cash';
        form.elements.notes.value = prefill.notes ?? '';
        document.getElementById('payment-tenant-name').textContent = name;
        document.getElementById('payment-modal').showModal();
    }
</script>

@if ($errors->payment->any())
    <script>
        openPayment(@js(old('tenant_id')), @js(old('tenant_name')), @js([
            'amount' => old('amount'),
            'transaction_date' => old('transaction_date'),
            'payment_method' => old('payment_method'),
            'notes' => old('notes'),
        ]));
    </script>
@endif