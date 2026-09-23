@extends('layouts.app')
@section('title', 'شركاء الصيانة — استقبال')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('handshake', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">شركاء الصيانة</h1>
                <p class="text-muted-foreground text-sm">فنيون خارجيون — تسجيل صياناتهم وتسوياتهم (التعديل والحذف للأدمن)</p>
            </div>
        </div>
        <button onclick="document.getElementById('partner-modal').showModal()" class="btn btn-primary">
            {!! icon('plus', 'h-4 w-4') !!}
            شريك جديد
        </button>
    </div>

    @include('reception.partials.tabs')

    {{-- إحصائيات --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ $stats['total'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الشركاء</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $stats['active'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">شركاء نشطون</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ $stats['totalRepairs'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي صياناتهم</div>
        </div>
        <div class="card p-4 text-center card-hover {{ $stats['totalDeferred'] > 0 ? 'border-amber-300 dark:border-amber-900' : '' }}">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ money($stats['totalDeferred']) }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">آجل مستحق عليهم</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">

        {{-- قائمة الشركاء --}}
        <div class="space-y-3">
            @forelse ($partners as $p)
                <div class="card p-5" x-data="{
                    tab: 'info',
                    devices: [{ device_type: '', brand: '', customer_name: '', issue_description: '', total_amount: '', paid_amount: '' }]
                }">
                    <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                                {!! icon('repeat', 'h-5 w-5') !!}
                            </div>
                            <div>
                                <h3 class="font-bold">{{ $p->name }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    @if ($p->phone)<span dir="ltr">{{ $p->phone }}</span> • @endif
                                    {{ $p->repairs_count }} صيانة • {{ $p->settlements_count }} تسوية
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($p->overLimit())
                                <span class="badge bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900 text-[10px]">تجاوز الحد!</span>
                            @endif
                            <span class="badge {{ $p->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }} text-[10px]">{{ $p->is_active ? 'نشط' : 'معطل' }}</span>
                        </div>
                    </div>

                    {{-- الرصيد --}}
                    <div class="grid grid-cols-2 gap-2 mb-3 text-center">
                        <div class="p-2.5 rounded-xl bg-green-50 dark:bg-green-950/30">
                            <div class="text-[10px] text-muted-foreground">مدفوع + مسدد</div>
                            <div class="font-extrabold text-sm text-green-600 dark:text-green-400">{{ money($p->paid_amount + $p->settled_amount) }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl {{ $p->balance_amount > 0 ? 'bg-amber-50 dark:bg-amber-950/30' : 'bg-muted/40' }}">
                            <div class="text-[10px] text-muted-foreground">آجل مستحق عليه</div>
                            <div class="font-extrabold text-sm {{ $p->balance_amount > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ money($p->balance_amount) }}</div>
                        </div>
                    </div>

                    @if ($p->credit_limit > 0)
                        <div class="text-[10px] text-muted-foreground mb-3">حد الآجل: {{ money($p->credit_limit) }}</div>
                    @endif

                    {{-- أزرار: الاستقبال يضيف فقط (زي الأصل canEdit=false canDelete=false) --}}
                    <div class="flex flex-wrap gap-2">
                        <button @click="tab = 'info'" class="btn btn-sm" :class="tab === 'info' ? 'btn-primary' : 'btn-outline'">البيانات</button>
                        <button @click="tab = 'repair'" class="btn btn-sm" :class="tab === 'repair' ? 'btn-primary' : 'btn-outline'">{!! icon('wrench', 'h-3 w-3') !!} صيانة جديدة</button>
                        <button @click="tab = 'settle'" class="btn btn-sm" :class="tab === 'settle' ? 'btn-primary' : 'btn-outline'">{!! icon('banknote', 'h-3 w-3') !!} تسوية</button>
                    </div>

                    <div class="mt-3">
                        {{-- البيانات --}}
                        <div x-show="tab === 'info'" class="text-sm text-muted-foreground space-y-1.5">
                            @if ($p->notes)<p>{{ $p->notes }}</p>@endif
                            <p class="text-xs">آخر صيانة: {{ $p->last_repair_date ? dt($p->last_repair_date) : '—' }} • سجّله: {{ $p->createdBy?->name }}</p>
                        </div>

                        {{-- صيانة جديدة — أجهزة متعددة + سعر اختياري --}}
                        <form x-show="tab === 'repair'" x-transition method="POST" action="{{ route('reception.partners.repairs', $p) }}" class="space-y-3" style="display:none">
                            @csrf
                            {{-- كل جهاز بصف مستقل — السعر اختياري --}}
                            <template x-for="(d, i) in devices" :key="i">
                                <div class="p-3 rounded-xl border border-border/70 bg-muted/20 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <p class="text-[11px] font-bold text-primary">جهاز #<span x-text="i + 1"></span></p>
                                        <button type="button" x-show="devices.length > 1" @click="devices.splice(i, 1)"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 hover:bg-red-200 cursor-pointer">{!! icon('x', 'h-3.5 w-3.5') !!}</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input :name="`devices[${i}][device_type]`" x-model="d.device_type" placeholder="نوع الجهاز *" class="input" list="partner-devices" required>
                                        <input :name="`devices[${i}][brand]`" x-model="d.brand" placeholder="الماركة" class="input">
                                        <input :name="`devices[${i}][customer_name]`" x-model="d.customer_name" placeholder="اسم عميله" class="input">
                                        <input :name="`devices[${i}][issue_description]`" x-model="d.issue_description" placeholder="وصف العطل" class="input">
                                        <input :name="`devices[${i}][total_amount]`" x-model="d.total_amount" type="number" step="0.01" min="0" placeholder="السعر (اختياري — بعدين)" class="input" dir="ltr">
                                        <input :name="`devices[${i}][paid_amount]`" x-model="d.paid_amount" type="number" step="0.01" min="0" placeholder="مدفوع دلوقتي (اختياري)" class="input" dir="ltr">
                                    </div>
                                    <p class="text-[10px] text-muted-foreground" x-show="!d.total_amount">السعر فاضي = هيتسجل «بدون سعر» وتحدده بعدين من قائمة الصيانات</p>
                                </div>
                            </template>
                            <button type="button" @click="devices.push({ device_type: '', brand: '', customer_name: '', issue_description: '', total_amount: '', paid_amount: '' })"
                                    class="w-full py-2 rounded-xl border-2 border-dashed border-primary/40 text-primary text-xs font-bold hover:bg-primary/5 transition-colors flex items-center justify-center gap-1.5">
                                {!! icon('plus', 'h-3.5 w-3.5') !!} إضافة جهاز آخر لنفس الشريك
                            </button>
                            <div class="grid grid-cols-2 gap-2">
                                <input name="date" type="date" value="{{ now()->toDateString() }}" class="input" required>
                                <input name="notes" placeholder="ملاحظات (بتطبق على الكل)" class="input">
                            </div>
                            <p class="text-[10px] text-muted-foreground">الشريك ممكن يجيب أكتر من جهاز في المرة الواحدة — المدفوع بيتحسب في إيراد اليوم والباقي مستحق عليه</p>
                            <button class="btn btn-primary btn-sm w-full">
                                {!! icon('wrench', 'h-3.5 w-3.5') !!} تسجيل <span x-text="devices.length"></span> صيانة
                            </button>
                        </form>

                        {{-- تسوية --}}
                        <form x-show="tab === 'settle'" x-transition method="POST" action="{{ route('reception.partners.settlements', $p) }}" class="space-y-2" style="display:none">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <input name="amount" type="number" step="0.01" min="0.01" placeholder="مبلغ التسوية *" class="input" dir="ltr" required>
                                <input name="date" type="date" value="{{ now()->toDateString() }}" class="input" required>
                            </div>
                            <input name="notes" placeholder="ملاحظات" class="input">
                            <p class="text-[10px] text-muted-foreground">المستحق حالياً: {{ money($p->balance_amount) }} — التسوية بتتحسب في إيراد اليوم</p>
                            <button class="btn btn-success btn-sm w-full">{!! icon('banknote', 'h-3.5 w-3.5') !!} تسجيل التسوية</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center">
                    <p class="text-muted-foreground text-sm mb-3">مفيش شركاء — أضف أول شريك صيانة</p>
                    <button onclick="document.getElementById('partner-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} إضافة شريك</button>
                </div>
            @endforelse

            {{ $partners->links() }}
        </div>

        {{-- آخر الحركات --}}
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('wrench', 'h-4 w-4 text-primary') !!} آخر صيانات الشركاء</h2>
                <div class="space-y-2">
                    @forelse ($recentRepairs as $r)
                        <div class="p-3 rounded-xl bg-muted/40 text-sm">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <b>{{ $r->partner->name }}</b> — {{ $r->device_type }}{{ $r->brand ? ' ('.$r->brand.')' : '' }}
                                    <div class="text-[10px] text-muted-foreground">{{ dt($r->date) }} • مدفوع {{ money($r->paid_amount) }}</div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    @if ($r->hasPrice())
                                        <span class="font-bold text-sm">{{ money($r->total_amount) }}</span>
                                        @if ($r->deferredAmount() > 0)
                                            <span class="badge bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900 text-[10px]">آجل: {{ money($r->deferredAmount()) }}</span>
                                        @else
                                            <span class="badge bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900 text-[10px]">مسدد</span>
                                        @endif
                                    @else
                                        <span class="badge bg-cyan-100 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-900 text-[10px]">السعر لسه محددش</span>
                                    @endif
                                </div>
                            </div>
                            {{-- تحديد السعر بعدين — فورم سريعة جوه الصف --}}
                            @if (! $r->hasPrice())
                                <form method="POST" action="{{ route('reception.partners.repairs.update', $r) }}" class="mt-2 flex items-center gap-1.5">
                                    @csrf @method('PUT')
                                    <input type="number" name="total_amount" step="0.01" min="0" placeholder="السعر" class="input flex-1 h-8 text-xs" dir="ltr" required>
                                    <input type="number" name="paid_amount" step="0.01" min="0" placeholder="مدفوع (اختياري)" class="input flex-1 h-8 text-xs" dir="ltr">
                                    <button type="submit" class="btn btn-primary btn-sm h-8 shrink-0">{!! icon('save', 'h-3 w-3') !!} تحديد</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground text-center py-3">مفيش صيانات شركاء</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('banknote', 'h-4 w-4 text-primary') !!} آخر التسويات</h2>
                <div class="space-y-2">
                    @forelse ($recentSettlements as $s)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-muted/40 text-sm">
                            <div>
                                <b>{{ $s->partner->name }}</b>
                                <div class="text-[10px] text-muted-foreground">{{ dt($s->date) }} {{ $s->notes ? '• '.$s->notes : '' }}</div>
                            </div>
                            <b class="text-green-600 dark:text-green-400">{{ money($s->amount) }}</b>
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground text-center py-3">مفيش تسويات</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<datalist id="partner-devices">
    @foreach (\App\Models\DeviceType::where('is_active', true)->get() as $dt)
        <option value="{{ $dt->name }}"></option>
    @endforeach
</datalist>

{{-- مودال إضافة شريك --}}
<dialog id="partner-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('reception.partners.store') }}" class="modal-box max-w-md">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة شريك صيانة</h3>
            <button type="button" onclick="document.getElementById('partner-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">اسم الشريك *</label>
                <input name="name" value="{{ old('name') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">الهاتف</label>
                    <input name="phone" value="{{ old('phone') }}" class="input" dir="ltr">
                </div>
                <div>
                    <label class="label">حد الآجل (ج.م)</label>
                    <input name="credit_limit" type="number" step="0.01" min="0" value="{{ old('credit_limit', 0) }}" class="input" dir="ltr">
                </div>
            </div>
            <div>
                <label class="label">ملاحظات</label>
                <textarea name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} إضافة الشريك</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
