{{-- رسائل النظام (flash) --}}
@php $flashes = [['type' => 'success', 'icon' => 'check-circle', 'msg' => session('success')], ['type' => 'error', 'icon' => 'alert-circle', 'msg' => session('error')], ['type' => 'info', 'icon' => 'info', 'msg' => session('info')]]; @endphp
@foreach ($flashes as $f)
    @if ($f['msg'])
        <div class="container mx-auto max-w-7xl px-4 sm:px-6 pt-4" x-data="{ show: true }" x-show="show" x-transition.leave.duration.300ms>
            <div class="flex items-center gap-3 rounded-xl px-4 py-3 border shadow-lg fade-in-up {{ $f['type'] === 'success' ? 'bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-900 text-green-800 dark:text-green-300' : ($f['type'] === 'error' ? 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900 text-red-800 dark:text-red-300' : 'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-900 text-blue-800 dark:text-blue-300') }}">
                {!! icon($f['icon'], 'h-5 w-5 shrink-0') !!}
                <p class="text-sm font-bold flex-1">{{ $f['msg'] }}</p>
                <button @click="show = false" class="opacity-60 hover:opacity-100 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
            </div>
        </div>
    @endif
@endforeach

{{-- أخطاء التحقق --}}
@if ($errors->any())
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 pt-4">
        <div class="rounded-xl px-4 py-3 border bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-900 text-red-800 dark:text-red-300">
            <div class="flex items-center gap-2 font-bold text-sm mb-1">{!! icon('alert-circle', 'h-4 w-4') !!} راجع البيانات التالية:</div>
            <ul class="text-xs space-y-0.5 mr-6 list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
