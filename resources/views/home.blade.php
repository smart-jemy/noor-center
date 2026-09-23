@extends('layouts.app')
@section('title', 'الرئيسية')
@section('meta_desc', 'صيانة جميع الأجهزة المنزلية في 6 أكتوبر وحدائق الأهرام — فنيون معتمدون، خدمة في نفس اليوم، ضمان على الإصلاح.')

@section('content')
<div class="flex flex-col" style="min-height: calc(100vh - 4rem - 1px)">

    {{-- Hero --}}
    <section class="flex-1 flex items-center py-6 md:py-8">
        <div class="container mx-auto max-w-6xl px-4 sm:px-6">
            <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 items-center">

                {{-- التسويق --}}
                <div class="space-y-4 text-center md:text-right order-2 lg:order-1 fade-in-up">
                    <div class="inline-flex items-center gap-1.5 bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-900 rounded-full px-3 py-1 text-xs font-bold">
                        {!! icon('map-pin', 'h-3 w-3') !!}
                        نخدم 6 أكتوبر وحدائق الأهرام
                    </div>

                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                        صيانة الأجهزة المنزلية
                        <span class="block text-primary mt-1">في منزلك بكل سهولة</span>
                    </h1>

                    <p class="text-base text-muted-foreground leading-relaxed max-w-md mx-auto md:mx-0">
                        تكييف، غسالة، ثلاجة، بوتاجاز، دش... أي جهاز به عطل؟ اطلب صيانة في دقائق،
                        الفني يصلك في نفس اليوم، والدفع كاش بعد الانتهاء.
                    </p>

                    <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                        <a href="{{ auth()->check() ? route('requests.create') : route('register') }}"
                           class="btn btn-primary btn-lg shadow-lg shadow-primary/20">
                            {!! icon('wrench', 'h-4 w-4') !!}
                            اطلب صيانة الآن
                        </a>
                        <a href="tel:{{ $siteSettings->phone }}" class="btn btn-outline btn-lg">
                            {!! icon('phone', 'h-4 w-4') !!}
                            اتصل بنا
                        </a>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1 text-xs text-muted-foreground justify-center md:justify-start">
                        <div class="flex items-center gap-1">{!! icon('check-circle', 'h-3.5 w-3.5 text-primary') !!} <span>بدون رسوم خفية</span></div>
                        <div class="flex items-center gap-1">{!! icon('check-circle', 'h-3.5 w-3.5 text-primary') !!} <span>ضمان على الإصلاح</span></div>
                        <div class="flex items-center gap-1">{!! icon('check-circle', 'h-3.5 w-3.5 text-primary') !!} <span>فنيون معتمدون</span></div>
                    </div>
                </div>

                {{-- شبكة الأجهزة --}}
                <div class="order-1 lg:order-2">
                    <div class="relative">
                        <div class="absolute -top-3 -left-3 z-10 bg-card rounded-xl shadow-lg p-2 border border-border/60">
                            <div class="flex items-center gap-1.5">
                                {!! icon('star', 'h-4 w-4 text-amber-500') !!}
                                <span class="text-xs font-bold">تقييم {{ number_format($avgRating, 1) }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border-2 border-primary/10 bg-gradient-to-br from-card to-amber-50/50 dark:to-amber-950/20 p-5 shadow-xl">
                            <div class="grid grid-cols-4 gap-3">
                                @forelse ($deviceTypes as $dt)
                                    <a href="{{ auth()->check() ? route('requests.create') : route('register') }}"
                                       class="aspect-square rounded-xl bg-card border border-border/60 flex flex-col items-center justify-center gap-1 hover:scale-105 hover:border-primary/40 hover:shadow-md transition-all cursor-pointer">
                                        {!! device_icon($dt->name, 'h-6 w-6 text-primary') !!}
                                        <span class="text-[10px] text-muted-foreground font-medium">{{ $dt->name }}</span>
                                    </a>
                                @empty
                                    <div class="col-span-4 text-center text-muted-foreground text-sm py-6">أجهزتك كلها شغالة؟ تمام 😄</div>
                                @endforelse
                            </div>

                            <div class="mt-4 p-3 rounded-xl bg-primary/5 border border-primary/10">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-foreground shrink-0">
                                        {!! icon('wrench', 'h-4 w-4') !!}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-bold">صيانة جميع الأجهزة</p>
                                        <p class="text-[10px] text-muted-foreground">اكتب أي جهاز ونصلك</p>
                                    </div>
                                    {!! icon('arrow-left', 'h-4 w-4 text-muted-foreground') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ليه مركز نور --}}
    <section class="py-10 bg-card/50 border-y border-border/40">
        <div class="container mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="text-2xl font-extrabold text-center mb-2">ليه <span class="gradient-text">مركز نور</span>؟</h2>
            <p class="text-center text-muted-foreground text-sm mb-8">خدمة صيانة تلاقي فيها كل اللي محتاجه</p>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-5 text-center card-hover">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">{!! icon('zap', 'h-6 w-6') !!}</div>
                    <h3 class="font-bold text-sm mb-1">خدمة في نفس اليوم</h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">اطلب دلوقتي والفني يوصلك في معادك — من غير تأجيل</p>
                </div>
                <div class="card p-5 text-center card-hover">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">{!! icon('shield-check', 'h-6 w-6') !!}</div>
                    <h3 class="font-bold text-sm mb-1">ضمان على الإصلاح</h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">كل صيانة بضمان حسب نوع الجهاز والعطل — راحتك أولاً</p>
                </div>
                <div class="card p-5 text-center card-hover">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">{!! icon('wallet', 'h-6 w-6') !!}</div>
                    <h3 class="font-bold text-sm mb-1">دفع بعد الانتهاء</h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">كاش بعد ما تتأكد إن الجهاز شغال زي الفل — من غير مقدم</p>
                </div>
                <div class="card p-5 text-center card-hover">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">{!! icon('award', 'h-6 w-6') !!}</div>
                    <h3 class="font-bold text-sm mb-1">فنيون معتمدون</h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">فنيون متخصصين لكل نوع جهاز بخبرة سنين في المجال</p>
                </div>
            </div>
        </div>
    </section>

    {{-- إزاي تطلب --}}
    <section class="py-10">
        <div class="container mx-auto max-w-4xl px-4 sm:px-6">
            <h2 class="text-2xl font-extrabold text-center mb-8">اطلب صيانة في <span class="gradient-text">3 خطوات</span></h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="card p-6 relative">
                    <span class="absolute top-4 left-4 text-4xl font-extrabold text-primary/10 select-none">1</span>
                    {!! icon('edit', 'h-7 w-7 text-primary mb-3') !!}
                    <h3 class="font-bold mb-1">اكتب تفاصيل الجهاز</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">نوع الجهاز، الماركة، ووصف بسيط للعطل — لو فيه صور ابعتها</p>
                </div>
                <div class="card p-6 relative">
                    <span class="absolute top-4 left-4 text-4xl font-extrabold text-primary/10 select-none">2</span>
                    {!! icon('calendar', 'h-7 w-7 text-primary mb-3') !!}
                    <h3 class="font-bold mb-1">حدد المعاد المناسب</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">اختار اليوم والفترة اللي تناسبك — صباحاً أو مساءً</p>
                </div>
                <div class="card p-6 relative">
                    <span class="absolute top-4 left-4 text-4xl font-extrabold text-primary/10 select-none">3</span>
                    {!! icon('check-circle', 'h-7 w-7 text-primary mb-3') !!}
                    <h3 class="font-bold mb-1">استنى الفني عندك</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">الفني يوصلك في المعاد ويتصل بيك قبلها — والصيانة تبدأ فوراً</p>
                </div>
            </div>
        </div>
    </section>

    {{-- التقييمات --}}
    @if ($reviews->isNotEmpty())
    <section class="py-10 bg-card/50 border-t border-border/40">
        <div class="container mx-auto max-w-6xl px-4 sm:px-6">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold mb-1">عملاؤنا <span class="gradient-text">يتكلمون</span></h2>
                <div class="flex items-center justify-center gap-1 text-amber-500 my-2">
                    @for ($i = 1; $i <= 5; $i++)
                        {!! icon('star', 'h-4 w-4'.($i <= round($avgRating) ? ' fill-amber-500 text-amber-500' : '')) !!}
                    @endfor
                    <span class="text-sm font-bold text-foreground mr-1">{{ number_format($avgRating, 1) }} من 5</span>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($reviews as $review)
                    <div class="card p-5 card-hover">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-sm font-bold">
                                    {{ mb_substr($review->customer?->name ?? '؟', 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold">{{ $review->customer?->name }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ $review->request?->device_type }} — {{ dt($review->created_at) }}</div>
                                </div>
                            </div>
                            <div class="flex gap-0.5 text-amber-500 text-xs">
                                @for ($i = 1; $i <= 5; $i++)
                                    {!! icon('star', 'h-3 w-3'.($i <= $review->rating ? ' fill-amber-500 text-amber-500' : '')) !!}
                                @endfor
                            </div>
                        </div>
                        <p class="text-sm text-muted-foreground leading-relaxed">{{ $review->comment }}</p>
                        @if ($review->admin_reply)
                            <div class="mt-3 p-3 rounded-xl bg-primary/5 border border-primary/10">
                                <div class="text-[10px] font-bold text-primary mb-0.5">رد مركز نور</div>
                                <p class="text-xs text-muted-foreground leading-relaxed">{{ $review->admin_reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CTA أخير --}}
    <section class="py-12">
        <div class="container mx-auto max-w-4xl px-4 sm:px-6">
            <div class="rounded-3xl bg-gradient-to-br from-primary to-primary/80 text-primary-foreground p-8 md:p-12 text-center shadow-2xl shadow-primary/20">
                <h2 class="text-2xl md:text-3xl font-extrabold mb-3">جهازك فيه عطل دلوقتي؟</h2>
                <p class="text-sm md:text-base opacity-90 mb-6 max-w-lg mx-auto leading-relaxed">
                    متأجلش — العطل الصغير بيكبر. اطلب صيانة النهاردة وخلي خبراء نور يتكفلوا بجهازك.
                </p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ auth()->check() ? route('requests.create') : route('register') }}" class="btn bg-white text-primary btn-lg hover:opacity-90">
                        {!! icon('wrench', 'h-4 w-4') !!}
                        اطلب صيانة الآن
                    </a>
                    <a href="{{ route('track') }}" class="btn border border-white/40 text-white btn-lg hover:bg-white/10">
                        {!! icon('search', 'h-4 w-4') !!}
                        تتبع طلبك
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
