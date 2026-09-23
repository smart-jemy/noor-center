@extends('layouts.app')
@section('title', 'فنيو قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8" x-data="{ addOpen: false }">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('hard-hat', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">فنيو القسم</h1>
                <p class="text-muted-foreground text-sm">{{ $technicians->count() }} فني — إدارة فريقك</p>
            </div>
        </div>
        <button @click="addOpen = !addOpen" class="btn btn-primary">
            {!! icon('user-plus', 'h-4 w-4') !!}
            إضافة فني
        </button>
    </div>

    @include('department.partials.tabs')

    {{-- نموذج إضافة فني --}}
    <div x-show="addOpen" x-transition class="card p-5 mb-4 border-primary/30" style="display: none">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('user-plus', 'h-4 w-4 text-primary') !!} إضافة فني جديد لفريقك</h2>
        <form method="POST" action="{{ route('department.technicians.store') }}" class="space-y-3">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="label">اسم الفني <span class="text-destructive">*</span></label>
                    <input name="name" value="{{ old('name') }}" placeholder="الاسم الكامل" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                    @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">الموبايل (بيستخدمه في الدخول) <span class="text-destructive">*</span></label>
                    <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right" class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required>
                    @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="label">كلمة المرور <span class="text-destructive">*</span></label>
                    <input name="password" type="text" value="{{ old('password') }}" placeholder="كلمة مرور مؤقتة للفني" class="input {{ $errors->has('password') ? 'input-error' : '' }}" dir="ltr" required>
                    @error('password')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">التخصص</label>
                    <input name="specialty" value="{{ old('specialty') }}" placeholder="مثال: تكييف، غسالات..." class="input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('user-plus', 'h-4 w-4') !!} إضافة الفني</button>
        </form>
    </div>

    {{-- قائمة الفنيين --}}
    <div class="grid sm:grid-cols-2 gap-3">
        @forelse ($technicians as $t)
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary text-lg font-bold shrink-0">
                            {{ mb_substr($t->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold">{{ $t->name }}</h3>
                            <p class="text-xs text-muted-foreground">
                                <span dir="ltr">{{ $t->phone }}</span>
                                @if ($t->specialty) • {{ $t->specialty }} @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge {{ $t->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }} text-[10px]">{{ $t->is_active ? 'نشط' : 'معطل' }}</span>
                        <form method="POST" action="{{ route('department.technicians.toggle', $t) }}">
                            @csrf
                            <button class="btn btn-outline btn-sm" title="{{ $t->is_active ? 'تعطيل' : 'تفعيل' }}">{!! icon('power', 'h-3.5 w-3.5') !!}</button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                    <div class="p-2.5 rounded-xl bg-muted/40">
                        <div class="text-[10px] text-muted-foreground">طلبات مسندة</div>
                        <div class="font-extrabold text-sm">{{ $t->assigned_requests_count }}</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-muted/40">
                        <div class="text-[10px] text-muted-foreground">تقييماته</div>
                        <div class="font-extrabold text-sm">{{ $t->reviews_count }}</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-muted/40">
                        <div class="text-[10px] text-muted-foreground">انضم</div>
                        <div class="font-extrabold text-xs mt-1">{{ dt($t->created_at) }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-10 text-center sm:col-span-2">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    {!! icon('hard-hat', 'h-7 w-7') !!}
                </div>
                <h3 class="font-bold mb-1">مفيش فنيين في فريقك</h3>
                <p class="text-muted-foreground text-sm">أضف أول فني — هيقدر يشوف الطلبات المسندة له ويحدث حالتها</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
