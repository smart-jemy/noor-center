@extends('admin.layout')
@section('title', 'التقييمات — لوحة التحكم')
@section('admin_title', 'التقييمات')
@section('admin_subtitle', 'مراجعة تقييمات العملاء — قبول / رفض / رد')

@section('admin_content')
{{-- إحصائيات --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="card p-4 text-center card-hover {{ request('status') === 'PENDING' ? 'border-amber-300' : '' }}">
        <a href="{{ route('admin.reviews.index', ['status' => 'PENDING']) }}" class="block">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">في الانتظار</div>
        </a>
    </div>
    <div class="card p-4 text-center card-hover {{ request('status') === 'APPROVED' ? 'border-green-300' : '' }}">
        <a href="{{ route('admin.reviews.index', ['status' => 'APPROVED']) }}" class="block">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $stats['approved'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">مقبولة (ظاهرة بالموقع)</div>
        </a>
    </div>
    <div class="card p-4 text-center card-hover {{ request('status') === 'REJECTED' ? 'border-red-300' : '' }}">
        <a href="{{ route('admin.reviews.index', ['status' => 'REJECTED']) }}" class="block">
            <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">مرفوضة</div>
        </a>
    </div>
    <div class="card p-4 text-center card-hover">
        <div class="flex items-center justify-center gap-1 text-2xl font-extrabold text-amber-500">
            {!! icon('star', 'h-6 w-6 fill-amber-500 text-amber-500') !!}
            {{ number_format($stats['avgRating'], 1) }}
        </div>
        <div class="text-xs text-muted-foreground mt-0.5">متوسط المقيم</div>
    </div>
</div>

{{-- فلاتر إضافية --}}
<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <select name="rating" class="input w-auto">
            <option value="">كل النجوم</option>
            @for ($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} نجوم</option>
            @endfor
        </select>
        <button type="submit" class="btn btn-outline btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة بالنجوم</button>
        @if (request()->hasAny(['rating', 'status']))
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-ghost btn-sm">مسح</a>
        @endif
    </div>
</form>

{{-- القائمة --}}
<div class="grid md:grid-cols-2 gap-4">
    @forelse ($reviews as $review)
        <div class="card p-5" x-data="{ reply: false }">
            <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-sm font-bold shrink-0">
                        {{ mb_substr($review->customer?->name ?? '؟', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-sm">{{ $review->customer?->name }}</div>
                        <div class="text-[10px] text-muted-foreground">
                            {{ $review->request?->device_type }} — <span dir="ltr">{{ $review->request?->order_number }}</span> — {{ dt($review->created_at) }}
                        </div>
                    </div>
                </div>
                <span class="badge {{ $review->statusColor() }}">{{ $review->statusLabel() }}</span>
            </div>

            <div class="flex gap-0.5 text-amber-500 mb-2">
                @for ($i = 1; $i <= 5; $i++)
                    {!! icon('star', 'h-4 w-4'.($i <= $review->rating ? ' fill-amber-500 text-amber-500' : '')) !!}
                @endfor
            </div>

            <p class="text-sm text-muted-foreground leading-relaxed mb-3">{{ $review->comment }}</p>

            @if ($review->admin_reply)
                <div class="p-3 rounded-xl bg-primary/5 border border-primary/10 mb-3">
                    <div class="text-[10px] font-bold text-primary mb-0.5">ردك</div>
                    <p class="text-xs text-muted-foreground">{{ $review->admin_reply }}</p>
                </div>
            @endif

            {{-- الإجراءات --}}
            <div class="flex flex-wrap gap-2">
                @if ($review->status !== 'APPROVED')
                    <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                        @csrf
                        <button class="btn btn-success btn-sm">{!! icon('check', 'h-3.5 w-3.5') !!} قبول ونشر</button>
                    </form>
                @endif
                @if ($review->status !== 'REJECTED')
                    <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                        @csrf
                        <button class="btn btn-outline btn-sm">{!! icon('x', 'h-3.5 w-3.5') !!} رفض</button>
                    </form>
                @endif
                <button @click="reply = !reply" class="btn btn-outline btn-sm">{!! icon('message', 'h-3.5 w-3.5') !!} {{ $review->admin_reply ? 'تعديل الرد' : 'رد' }}</button>
                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('حذف التقييم نهائياً؟')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                </form>
            </div>

            {{-- الرد --}}
            <form x-show="reply" x-transition method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="mt-3" style="display:none">
                @csrf
                <textarea name="admin_reply" rows="2" placeholder="ردك على التقييم (هيظهر للجميع مع التقييم)" class="input" required>{{ $review->admin_reply }}</textarea>
                <button type="submit" class="btn btn-primary btn-sm mt-2">حفظ الرد</button>
            </form>
        </div>
    @empty
        <div class="card p-10 text-center md:col-span-2">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                {!! icon('star', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">مفيش تقييمات {{ request('status') ? 'في الحالة دي' : 'بعد' }}</h3>
            <p class="text-muted-foreground text-sm">التقييمات بتوصل لما العملاء يقيّموا صياناتهم المكتملة</p>
        </div>
    @endforelse
</div>

{{ $reviews->links() }}
@endsection
