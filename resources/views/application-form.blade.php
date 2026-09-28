<x-layout bodyClass="font-sans bg-ink min-h-screen flex flex-col">
    <x-slot:title>
        Application Form - DORA
    </x-slot:title>
    <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_20%_30%,rgba(193,113,47,0.14),transparent_40%),radial-gradient(circle_at_80%_70%,rgba(47,111,110,0.18),transparent_45%)]"></div>
    <a href="{{ url('/') }}" class="absolute top-6 left-6 z-10 text-sm font-semibold text-paper/80 hover:text-paper transition">← Back</a>
    
    @if (session('applied'))
    
        {{-- Success state --}}
        <div class="relative z-10 w-full max-w-[400px] bg-paper3 rounded-[20px] p-9 shadow-2xl text-center">
        <h1 class="font-display text-xl text-ink">Application received!</h1>
        <p class="text-[13.5px] text-inkSoft mt-2 leading-relaxed">
            Thanks for applying to Cozy Haven. Admin will review your details and reach out
            on the contact info you provided once a decision is made.
        </p>
        <a href="{{ url('/') }}" class="block mt-5">
            <x-button variant="copper" block>Back to home</x-button>
        </a>
        </div>
    
    @else
    
        {{-- Application form --}}
        <div class="relative my-5 flex flex-col items-center px-4" >
            <x-button variant="secondary" href="{{ route('home') }}" class="inline-flex self-start gap-2">Back to home</x-button>
            
            <div class="relative z-10 w-full max-w-[460px] bg-paper3 rounded-[20px] p-9 shadow-2xl">
    
                <div class="text-center mb-6">
                    <h1 class="font-display text-xl text-ink">Apply for a room</h1>
                    <p class="text-[13px] text-inkSoft mt-1">Cozy Haven Dormitory · San Ildefonso, Bulacan</p>
                </div>
            
                <form method="POST" action="" class="space-y-4">
                    @csrf
            
                    <div>
                        <x-form-input name="name" label="Full name" placeholder="Juan Dela Cruz" required />
                    </div>
            
                    <div>
                        <x-form-input name="contact" label="Contact number" placeholder="0917 000 0000" required />
                    </div>
            
                    <div>
                        <x-form-input name="email" type="email" label="Email" placeholder="name@example.com" />
                    </div>
            
                    <div>
                        <x-form-input
                            name="desired_room"
                            type="select"
                            label="Preferred room"
                            :options="['' => 'No preference', 'Room1' => 'Room1']"
                        />
                    </div>
            
                    <div>
                        <x-form-input
                            name="move_in_date"
                            type="date"
                            label="Preferred move-in date"
                            :value="now()->toDateString()"
                        />
                    </div>
            
                    <div>
                        <x-form-input
                            name="message"
                            type="textarea"
                            label="Message (optional)"
                            rows="3"
                            placeholder="Anything admin should know — work schedule, referral, roommate preference..."
                        />
                    </div>
            
                    <x-button variant="copper" type="submit" block>Submit application</x-button>
                </form>
            
                <div class="mt-4 bg-paper2 rounded-[10px] px-3.5 py-3 text-[12.5px] text-inkSoft leading-relaxed">
                    Submitting here does not create a login. Admin will review your application and set up your tenant account once approved.
                </div>
    
            </div>
        </div>
        
    
    @endif

</x-layout>