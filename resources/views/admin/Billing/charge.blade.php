{{-- Add charge modal --}}
<dialog id="charge-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 class="font-display text-xl font-bold text-ink">Add charge</h2>
    <p id="charge-tenant-name" class="mt-1 text-sm text-inkSoft"></p>

    @if ($errors->charge->any())
        <div class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
            @foreach ($errors->charge->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form id="charge-form" method="POST" action="" class="mt-4 flex flex-col gap-4">
        @csrf
        <input type="hidden" name="tenant_id">
        <input type="hidden" name="tenant_name">

        <label class="text-sm font-semibold text-ink">Description
            <input name="description" required maxlength="255" placeholder="e.g. October rent"
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Amount (₱)
            <input type="number" step="0.01" min="0.01" name="amount" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <div class="grid grid-cols-2 gap-4">
            <label class="text-sm font-semibold text-ink">Charge date
                <input type="date" name="transaction_date" required
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-ink">Due date (optional)
                <input type="date" name="due_date"
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>
        </div>

        <label class="text-sm font-semibold text-ink">Notes (optional)
            <textarea name="notes" rows="2" maxlength="500"
                      class="mt-1 w-full rounded-xl border border-ink/10 bg-paper p-3 text-sm font-normal"></textarea>
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
            <button type="submit" class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-tealDeep">Save charge</button>
        </div>
    </form>
</dialog>

<script>
    const chargeUrl = @js(route('admin.billing.charges.store', ['tenant' => '__ID__']));
    const chargeToday = @js(now()->toDateString());

    function openCharge(id, name, prefill = {}) {
        const form = document.getElementById('charge-form');
        form.reset();
        form.action = chargeUrl.replace('__ID__', id);
        form.elements.tenant_id.value = id;
        form.elements.tenant_name.value = name;
        form.elements.description.value = prefill.description ?? '';
        form.elements.amount.value = prefill.amount ?? '';
        form.elements.transaction_date.value = prefill.transaction_date ?? chargeToday;
        form.elements.due_date.value = prefill.due_date ?? '';
        form.elements.notes.value = prefill.notes ?? '';
        document.getElementById('charge-tenant-name').textContent = name;
        document.getElementById('charge-modal').showModal();
    }
</script>

@if ($errors->charge->any())
    <script>
        openCharge(@js(old('tenant_id')), @js(old('tenant_name')), @js([
            'description' => old('description'),
            'amount' => old('amount'),
            'transaction_date' => old('transaction_date'),
            'due_date' => old('due_date'),
            'notes' => old('notes'),
        ]));
    </script>
@endif