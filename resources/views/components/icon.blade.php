@props(['name' => 'home'])
@php($paths = [
'home'=>'m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z',
'announcements'=>'M4 10v4h4l10 5V5L8 10H4m4 4 2 7h3l-2-6M21 9v6',
'grades'=>'m3 9 9-5 9 5-9 5-9-5m4 3v6q5 4 10 0v-6M21 9v8',
'students'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m8-7a4 4 0 0 1 0 8m5 9v-2a4 4 0 0 0-3-4',
 'teachers'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M16 3h6v12h-5',
'achievers'=>'M8 3h8v7a4 4 0 0 1-8 0V3m0 2H4v3a4 4 0 0 0 4 4m8-7h4v3a4 4 0 0 1-4 4m-4 2v5m-4 2h8m-6-2h4',
'overview'=>'M4 21h16M6 21V7l6-4 6 4v14M9 10h1m4 0h1m-6 4h1m4 0h1m-4 7v-3h2v3',
'attendance'=>'m8 12 3 3 5-6M5 4h14v17H5V4m3-2v4m8-4v4',
'marks'=>'M5 3h14v18H5V3m3 5h8m-8 4h8m-8 4h4',
'fees'=>'M3 6h18v14H3V6m0 4h18m-6 5h3M7 6V3h10v3',
'settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8m-9 4 2-1 1-3-1-2 2-2 2 1 3-1 1-2h3l1 2 3 1 2-1 2 2-1 2 1 3 2 1v3l-2 1-1 3 1 2-2 2-2-1-3 1-1 2h-3l-1-2-3-1-2 1-2-2 1-2-1-3-2-1z',
'arrow'=>'M4 12h16m-6-6 6 6-6 6','calendar'=>'M4 5h16v16H4V5m4-3v6m8-6v6M4 10h16',
'location'=>'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0m-8-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
'menu'=>'M4 6h16M4 12h16M4 18h16','close'=>'m6 6 12 12M6 18 18 6','logout'=>'M9 4H4v16h5m5-13 5 5-5 5m-7-5h12',
'book'=>'M12 5q-5-3-10 0v15q5-3 10 0m0-15q5-3 10 0v15q-5-3-10 0V5','check'=>'m5 12 4 4L19 6',
'holiday'=>'M12 3v2m0 14v2M3 12h2m14 0h2M6 6l1 1m10 10 1 1M6 18l1-1M17 7l1-1M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
'event'=>'m12 3 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z','meeting'=>'M3 4h18v13H8l-5 4V4m4 5h10m-10 4h6','general'=>'M12 8v1m0 3v5m0-15a10 10 0 1 0 0 20 10 10 0 0 0 0-20', 'exam'=>'M5 3h14v18H5V3m3 5h8m-8 4h8m-8 4h4'])
<svg {{ $attributes->merge(['class'=>'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['book'] }}"/></svg>
