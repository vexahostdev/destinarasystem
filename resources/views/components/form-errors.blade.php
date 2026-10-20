@if (isset($errors) && $errors->any())
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm transition-all" role="alert">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-rose-600 text-xl shrink-0 mt-0.5">error_outline</span>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-rose-950 mb-1">Terdapat kesalahan pengisian formulir:</h4>
                    <ul class="list-disc list-inside text-xs space-y-1 text-rose-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    </div>
@endif
