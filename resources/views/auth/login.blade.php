<x-layout bodyClass="font-sans bg-ink min-h-screen flex flex-col">
    <x-slot:title>
        Login - DORA
    </x-slot:title>
    <div class="flex flex-col items-center justify-center flex-1 px-4 py-12 sm:px-6 lg:px-8">
        <div class="relative z-10 w-full max-w-[400px] bg-paper3 rounded-[20px] p-9 shadow-2xl">
 
            <div class="text-center mb-6">
                <h1 class="font-display text-xl text-ink">Welcome to DORA</h1>
                <p class="text-[13px] text-inkSoft mt-1">Cozy Haven Dormitory Management</p>
            </div>
    
        {{-- Role tabs --}}
            <div class="flex bg-paper2 rounded-full p-1 mb-6" id="role-tabs">
                <button type="button" onclick="showTab('admin')" id="tab-btn-admin"
                    class="flex-1 py-2 rounded-full text-[13.5px] font-semibold transition bg-ink text-paper">
                    Admin
                </button>
                <button type="button" onclick="showTab('tenant')" id="tab-btn-tenant"
                    class="flex-1 py-2 rounded-full text-[13.5px] font-semibold transition text-inkSoft">
                    Tenant
                </button>
            </div>
        
            @if ($errors->any())
            <div class="bg-danger/10 text-danger text-sm rounded-xl px-3.5 py-2.5 mb-4">
                {{ $errors->first() }}
            </div>
            @endif
    
        {{-- Admin login form --}}
            <form id="form-admin" method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="login_role" value="admin">
                <div>
                    <x-form-input name="email" label="Email" placeholder="admin@example.com" required />
                </div>
                <div>
                    <x-form-input name="password" label="Password" placeholder="••••••••" type="password" required />
                </div>
                <x-button variant="copper" type="submit" block>Log in as admin</x-button>
            </form>
    
        {{-- Tenant login form --}}
            <form id="form-tenant" method="POST" action="{{ route('login.store') }}" class="space-y-4 hidden">
            @csrf
            <input type="hidden" name="login_role" value="tenant">
            <div>
                <x-form-input name="email" label="Email" placeholder="juan@example.com" required />
            </div>
            <div>
                <x-form-input name="password" label="Password" placeholder="••••••••" type="password" required />
            </div>
                <x-button variant="teal" type="submit" block>Log in as tenant</x-button>
            </form>
        
            <div class="mt-4 bg-paper2 rounded-[10px] px-3.5 py-3 text-[12.5px] text-inkSoft leading-relaxed" id="login-hint">
                Demo credentials — <b class="text-ink">admin</b> / <b class="text-ink">admin123</b>
            </div>
 
  </div>
 
  <script>
    function showTab(role){
      const isAdmin = (role === 'admin');
      document.getElementById('form-admin').classList.toggle('hidden', !isAdmin);
      document.getElementById('form-tenant').classList.toggle('hidden', isAdmin);
      document.getElementById('tab-btn-admin').className =
        'flex-1 py-2 rounded-full text-[13.5px] font-semibold transition ' + (isAdmin ? 'bg-ink text-paper' : 'text-inkSoft');
      document.getElementById('tab-btn-tenant').className =
        'flex-1 py-2 rounded-full text-[13.5px] font-semibold transition ' + (!isAdmin ? 'bg-ink text-paper' : 'text-inkSoft');
      document.getElementById('login-hint').innerHTML = isAdmin
        ? 'Demo credentials — <b class="text-ink">admin</b> / <b class="text-ink">admin123</b>'
        : 'Demo password for every tenant account — <b class="text-ink">cozyhaven</b>';
    }
  </script>

    </div>
</x-layout>
