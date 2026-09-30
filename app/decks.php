<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

$pageTitle = "Decks"; 
$currentPage = "decks";
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '\includes\head.php';?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '\includes\sidebar.php'; ?>


     <main class="pl-72 pt-12 transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '\includes\header-data.php'; ?>
        <?php include __DIR__ . '\includes\header.php'; ?>
    <div class="max-w-7xl mx-auto px-8 py-8">
        <div class="flex flex-col w-full gap-8">
<!-- Streak Heat Map & Streak Cards Section -->
<section class="flex flex-col gap-6 w-full" id="decks-gallery-section">
<!-- Section Header & Filter Controls -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-xl bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant shadow-xs">
<span class="material-symbols-outlined text-[22px]">style</span>
</div>
<div>
<div class="flex items-center gap-2.5">
<h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface">Deck Gallery</h2>
<span class="px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs">6 Decks</span>
</div>

</div>
</div>
<div class="flex flex-wrap items-center gap-2.5">
<!-- Filter Pills -->

<!-- View Mode Toggle (Grid / List) -->
<div aria-label="View toggle" class="inline-flex items-center p-1 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface-variant" role="group">
<button aria-pressed="false" class="flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors" id="view-grid-btn" title="Grid View" type="button">
<span class="material-symbols-outlined text-[20px]">grid_view</span>
</button>
<button aria-pressed="true" class="flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all" id="view-list-btn" title="List View" type="button">
<span class="material-symbols-outlined text-[20px]">view_list</span>
</button>
</div>
<button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold shadow-sm hover:shadow transition-all">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">New Deck</span>
</button>
</div>
</div>
<!-- Tiled Decks Grid Container -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-200 hidden" id="decks-grid-view">
<!-- Card 1: Reference Match - Object-Oriented Programming -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
<!-- Cover Image Area -->
<div class="relative h-44 w-full bg-surface-container-high overflow-hidden border-b border-outline-variant/20 flex items-center justify-center">
<img alt="Object-Oriented Programming Cover" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="https://lh3.googleusercontent.com/aida/AEtjO1X0KnSrhLuWbWdKFbldp4x8xEICpjyDqqIhWqwQids67zBETnc4VnHUXqokPz8Ry3Ss82SCSJBVDRswfZ_yp8ljPZI35RGwMK66ho91B48yxIqZlUH96742WN4Z334Dx47f7dJgzr3BSAA-Gbx7swVeg9V9zTEsydhK28mPP6x8rfXIDYdJMJkM7RmeOuB61J5w8y5I4Bev2fSV1LfU9KZqNpd4PFc3COydNzEcpk0TQKZiEr6mICkKB1kn">
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
<div class="absolute top-3 left-3">

</div>
<button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
<!-- Middle Metadata Area -->
<div class="p-5 flex-1 flex flex-col justify-between gap-4">
<div>
<h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
            Object-Oriented Programming
          </h3>
<div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
<div class="flex items-center gap-1.5">
<span class="material-symbols-outlined text-primary text-[18px]">style</span>
<span class="font-semibold text-on-surface">35 Cards</span>
</div>
<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold text-[12px]">
<span class="material-symbols-outlined text-[16px]">verified</span>
<span class="">90% Accuracy</span>
</div>
</div>
<!-- Progress Bar -->
<div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 90%"></div>
</div>
</div>
<!-- Bottom Buttons Row -->
<div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
<button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="">Browse Deck</span>
</button>
<button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">Add Card</span>
</button>
</div>
</div>
</div>
<!-- Card 2: Medical Neuroscience -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
<!-- Cover Image Area -->
<div class="relative h-44 w-full bg-surface-container-high overflow-hidden border-b border-outline-variant/20 flex items-center justify-center">
<img alt="Medical Neuroscience Cover" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="https://lh3.googleusercontent.com/aida/AEtjO1XfOdvUTT6hlq0pFOkgXnR2IS3xrLaDPW4IOtYbgOqTo1uxI_fZDuZX-0WE8wUxkhxGS7Qktokdh56fHi42Hf9ChbqbWwRcWPIJDi52pIyTF74PUXVkpEfiK4_tvt919gg772XH68vgYeWU5ha-kBBcmt5G6P9EdaxCKIIaaUdYVU70V1jc7NH_HGhGS_htOD5eCDHHzmrA3dg83QiTSFYdU-MQGEpp1i04Ufsvp33uviQxlKGzQVuP5khk">
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
<div class="absolute top-3 left-3">

</div>
<button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
<!-- Middle Metadata Area -->
<div class="p-5 flex-1 flex flex-col justify-between gap-4">
<div>
<h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
            Medical Neuroscience
          </h3>
<div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
<div class="flex items-center gap-1.5">
<span class="material-symbols-outlined text-primary text-[18px]">style</span>
<span class="font-semibold text-on-surface">340 Cards</span>
</div>
<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold text-[12px]">
<span class="material-symbols-outlined text-[16px]">verified</span>
<span class="">95% Accuracy</span>
</div>
</div>
<!-- Progress Bar -->
<div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 95%"></div>
</div>
</div>
<!-- Bottom Buttons Row -->
<div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
<button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="">Browse Deck</span>
</button>
<button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">Add Card</span>
</button>
</div>
</div>
</div>
<!-- Card 3: Spanish Vocabulary (C1) -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
<!-- Cover Image Area -->
<div class="relative h-44 w-full bg-surface-container-high overflow-hidden border-b border-outline-variant/20 flex items-center justify-center">
<img alt="Spanish Vocabulary Cover" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 bg-slate-900 p-2" src="https://lh3.googleusercontent.com/aida/AEtjO1XeKa_3TZCmvoq4pvHGB1OI-wU6ebCYJKyhYNiVIdCtKadLs7dG55aHzkh-lDcgIOFLrs4we8vzqWlj7UdaONbtONuLHGFyfNCObjAwVmTu0_PbgYa2ZT4ICpOc9iJqNWNepyxrNXFZXlKZx-7iU1Hoc5uqWd-LGFufQk012UdNkXKcb1DaWg3lW53nOgbqXlZTIW1uXFOrmm0AJ7WyjSctD7Wz_uPMLjRQuoiUNwNQHRLw1QLE04ZvmFGl">
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
<div class="absolute top-3 left-3">

</div>
<button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
<!-- Middle Metadata Area -->
<div class="p-5 flex-1 flex flex-col justify-between gap-4">
<div>
<h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
            Spanish Vocabulary (C1)
          </h3>
<div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
<div class="flex items-center gap-1.5">
<span class="material-symbols-outlined text-primary text-[18px]">style</span>
<span class="font-semibold text-on-surface">850 Cards</span>
</div>
<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold text-[12px]">
<span class="material-symbols-outlined text-[16px]">verified</span>
<span class="">91% Accuracy</span>
</div>
</div>
<!-- Progress Bar -->
<div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 91%"></div>
</div>
</div>
<!-- Bottom Buttons Row -->
<div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
<button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="">Browse Deck</span>
</button>
<button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">Add Card</span>
</button>
</div>
</div>
</div>
<!-- Card 4: System Design & Architecture -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
<!-- Cover Image Area -->
<div class="relative h-44 w-full bg-surface-container-high overflow-hidden border-b border-outline-variant/20 flex items-center justify-center">
<img alt="System Design Cover" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="https://lh3.googleusercontent.com/aida/AEtjO1XkOIw_tZYSUKiQMYyWCz66TpTi6ezUhGtU-k6CmTDu7LO_luy7F9uqAKVvGgVn19IJM4wgPELelHbuAXvVmQ3SVgS-5rCy_8gZL_G1ZNvt7jyJGntrqQxjB7bgF7vVLQeRMGk_AHmOLzR-2fcdFOWqkp5qqG42IBNY-eXS1ne9vyJk4m3t8XW-jrbJZxjMTOOC8uxnWxXAvkRWFJBYUwWvukdHHyTyGfUYkLHvmUCPOAByJU8j1ivOd96G">
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
<div class="absolute top-3 left-3">

</div>
<button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
<!-- Middle Metadata Area -->
<div class="p-5 flex-1 flex flex-col justify-between gap-4">
<div>
<h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
            System Design &amp; Architecture
          </h3>
<div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
<div class="flex items-center gap-1.5">
<span class="material-symbols-outlined text-primary text-[18px]">style</span>
<span class="font-semibold text-on-surface">210 Cards</span>
</div>
<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200/80 font-semibold text-[12px]">
<span class="material-symbols-outlined text-[16px]">speed</span>
<span class="">88% Accuracy</span>
</div>
</div>
<!-- Progress Bar -->
<div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
<div class="bg-amber-500 h-1.5 rounded-full" style="width: 88%"></div>
</div>
</div>
<!-- Bottom Buttons Row -->
<div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
<button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="">Browse Deck</span>
</button>
<button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">Add Card</span>
</button>
</div>
</div>
</div>
<!-- Card 5: Data Structures & Algorithms -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
<!-- Cover Image Area -->
<div class="relative h-44 w-full bg-surface-container-high overflow-hidden border-b border-outline-variant/20 flex items-center justify-center">
<img alt="Data Structures Cover" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 bg-slate-900 p-2" src="https://lh3.googleusercontent.com/aida/AEtjO1XP0TR_DXfH0izfhCqoQ5_xdLLKwexJIK6DhK5wU98Twr-YkTXu0YHfJ4WQHH3c1L5SjTfjHfFYh8MTQJvBZLjmz1aiv2iuagb7SAaUIDhGyWYuDr9-KtiJgeTkEEwhd15jHa65-HT3eFypOWGsBAzYCCe0_ctxNxMQELGrLGQ2EVfVayRpnv54DG4HuGdcDE4wu5jvt8UFKmamVLgWekA9D1j6i7eX5jej2HJsORAJoVcveGNt22JlLZ5f">
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
<div class="absolute top-3 left-3">

</div>
<button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
<!-- Middle Metadata Area -->
<div class="p-5 flex-1 flex flex-col justify-between gap-4">
<div>
<h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
            Data Structures &amp; Algorithms
          </h3>
<div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
<div class="flex items-center gap-1.5">
<span class="material-symbols-outlined text-primary text-[18px]">style</span>
<span class="font-semibold text-on-surface">420 Cards</span>
</div>
<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold text-[12px]">
<span class="material-symbols-outlined text-[16px]">verified</span>
<span class="">92% Accuracy</span>
</div>
</div>
<!-- Progress Bar -->
<div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 92%"></div>
</div>
</div>
<!-- Bottom Buttons Row -->
<div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
<button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="">Browse Deck</span>
</button>
<button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="">Add Card</span>
</button>
</div>
</div>
</div>
<!-- Card 6: Interactive Create New Deck Placeholder Tile -->
<div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-6 flex flex-col items-center justify-center text-center gap-3.5 bg-surface-container-low/40 hover:bg-surface-container-low transition-all cursor-pointer group min-h-[340px]">
<div class="w-14 h-14 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-110 shadow-sm transition-transform">
<span class="material-symbols-outlined text-[28px]">add_circle</span>
</div>
<div class="flex flex-col gap-1">
<h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">Create New Deck</h4>

</div>

</div>
</div>
<!-- List View Container (Hidden by default, toggled via JavaScript) -->
<div class="flex-col gap-3.5 transition-opacity duration-200 flex" id="decks-list-view">
<!-- List Item 1: Object-Oriented Programming -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
<div class="flex items-center gap-4 min-w-0">
<div class="w-14 h-14 rounded-xl overflow-hidden bg-surface-container-high flex-shrink-0 relative">
<img alt="Object-Oriented Programming" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida/AEtjO1X0KnSrhLuWbWdKFbldp4x8xEICpjyDqqIhWqwQids67zBETnc4VnHUXqokPz8Ry3Ss82SCSJBVDRswfZ_yp8ljPZI35RGwMK66ho91B48yxIqZlUH96742WN4Z334Dx47f7dJgzr3BSAA-Gbx7swVeg9V9zTEsydhK28mPP6x8rfXIDYdJMJkM7RmeOuB61J5w8y5I4Bev2fSV1LfU9KZqNpd4PFc3COydNzEcpk0TQKZiEr6mICkKB1kn">
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-2 flex-wrap">

<h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate">Object-Oriented Programming</h3>
</div>
<div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1 font-semibold text-on-surface"><span class="material-symbols-outlined text-[16px] text-primary">style</span> 35 Cards</span>
<span class="text-outline-variant">•</span>
<span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">schedule</span> Due in 2 hrs</span>
</div>
</div>
</div>
<div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
<div class="flex flex-col gap-1.5 w-36 sm:w-44">
<div class="flex items-center justify-between text-label-sm font-semibold">
<span class="text-on-surface-variant">Accuracy</span>
<span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/80">90%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 90%"></div>
</div>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="hidden sm:inline">Browse</span>
</button>
<button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="hidden sm:inline">Add Card</span>
</button>
<button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
</div>
</div>
<!-- List Item 2: Medical Neuroscience -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
<div class="flex items-center gap-4 min-w-0">
<div class="w-14 h-14 rounded-xl overflow-hidden bg-surface-container-high flex-shrink-0 relative">
<img alt="Medical Neuroscience" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida/AEtjO1XfOdvUTT6hlq0pFOkgXnR2IS3xrLaDPW4IOtYbgOqTo1uxI_fZDuZX-0WE8wUxkhxGS7Qktokdh56fHi42Hf9ChbqbWwRcWPIJDi52pIyTF74PUXVkpEfiK4_tvt919gg772XH68vgYeWU5ha-kBBcmt5G6P9EdaxCKIIaaUdYVU70V1jc7NH_HGhGS_htOD5eCDHHzmrA3dg83QiTSFYdU-MQGEpp1i04Ufsvp33uviQxlKGzQVuP5khk">
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-2 flex-wrap">

<h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate">Medical Neuroscience</h3>
</div>
<div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1 font-semibold text-on-surface"><span class="material-symbols-outlined text-[16px] text-primary">style</span> 340 Cards</span>
<span class="text-outline-variant">•</span>
<span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">schedule</span> Due tomorrow</span>
</div>
</div>
</div>
<div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
<div class="flex flex-col gap-1.5 w-36 sm:w-44">
<div class="flex items-center justify-between text-label-sm font-semibold">
<span class="text-on-surface-variant">Accuracy</span>
<span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/80">95%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 95%"></div>
</div>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="hidden sm:inline">Browse</span>
</button>
<button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="hidden sm:inline">Add Card</span>
</button>
<button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
</div>
</div>
<!-- List Item 3: Spanish Vocabulary (C1) -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
<div class="flex items-center gap-4 min-w-0">
<div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-900 p-1 flex-shrink-0 relative">
<img alt="Spanish Vocabulary" class="w-full h-full object-cover rounded-lg transition-transform duration-300 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida/AEtjO1XeKa_3TZCmvoq4pvHGB1OI-wU6ebCYJKyhYNiVIdCtKadLs7dG55aHzkh-lDcgIOFLrs4we8vzqWlj7UdaONbtONuLHGFyfNCObjAwVmTu0_PbgYa2ZT4ICpOc9iJqNWNepyxrNXFZXlKZx-7iU1Hoc5uqWd-LGFufQk012UdNkXKcb1DaWg3lW53nOgbqXlZTIW1uXFOrmm0AJ7WyjSctD7Wz_uPMLjRQuoiUNwNQHRLw1QLE04ZvmFGl">
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-2 flex-wrap">

<h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate">Spanish Vocabulary (C1)</h3>
</div>
<div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1 font-semibold text-on-surface"><span class="material-symbols-outlined text-[16px] text-primary">style</span> 850 Cards</span>
<span class="text-outline-variant">•</span>
<span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">schedule</span> Due in 3 days</span>
</div>
</div>
</div>
<div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
<div class="flex flex-col gap-1.5 w-36 sm:w-44">
<div class="flex items-center justify-between text-label-sm font-semibold">
<span class="text-on-surface-variant">Accuracy</span>
<span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/80">91%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 91%"></div>
</div>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="hidden sm:inline">Browse</span>
</button>
<button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="hidden sm:inline">Add Card</span>
</button>
<button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
</div>
</div>
<!-- List Item 4: System Design & Architecture -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
<div class="flex items-center gap-4 min-w-0">
<div class="w-14 h-14 rounded-xl overflow-hidden bg-surface-container-high flex-shrink-0 relative">
<img alt="System Design" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida/AEtjO1XkOIw_tZYSUKiQMYyWCz66TpTi6ezUhGtU-k6CmTDu7LO_luy7F9uqAKVvGgVn19IJM4wgPELelHbuAXvVmQ3SVgS-5rCy_8gZL_G1ZNvt7jyJGntrqQxjB7bgF7vVLQeRMGk_AHmOLzR-2fcdFOWqkp5qqG42IBNY-eXS1ne9vyJk4m3t8XW-jrbJZxjMTOOC8uxnWxXAvkRWFJBYUwWvukdHHyTyGfUYkLHvmUCPOAByJU8j1ivOd96G">
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-2 flex-wrap">

<h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate">System Design &amp; Architecture</h3>
</div>
<div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1 font-semibold text-on-surface"><span class="material-symbols-outlined text-[16px] text-primary">style</span> 210 Cards</span>
<span class="text-outline-variant">•</span>
<span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">schedule</span> Due in 5 hrs</span>
</div>
</div>
</div>
<div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
<div class="flex flex-col gap-1.5 w-36 sm:w-44">
<div class="flex items-center justify-between text-label-sm font-semibold">
<span class="text-on-surface-variant">Accuracy</span>
<span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/80">88%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
<div class="bg-amber-500 h-1.5 rounded-full" style="width: 88%"></div>
</div>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="hidden sm:inline">Browse</span>
</button>
<button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="hidden sm:inline">Add Card</span>
</button>
<button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
</div>
</div>
<!-- List Item 5: Data Structures & Algorithms -->
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
<div class="flex items-center gap-4 min-w-0">
<div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-900 p-1 flex-shrink-0 relative">
<img alt="Data Structures &amp; Algorithms" class="w-full h-full object-cover rounded-lg transition-transform duration-300 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida/AEtjO1XP0TR_DXfH0izfhCqoQ5_xdLLKwexJIK6DhK5wU98Twr-YkTXu0YHfJ4WQHH3c1L5SjTfjHfFYh8MTQJvBZLjmz1aiv2iuagb7SAaUIDhGyWYuDr9-KtiJgeTkEEwhd15jHa65-HT3eFypOWGsBAzYCCe0_ctxNxMQELGrLGQ2EVfVayRpnv54DG4HuGdcDE4wu5jvt8UFKmamVLgWekA9D1j6i7eX5jej2HJsORAJoVcveGNt22JlLZ5f">
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-2 flex-wrap">

<h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate">Data Structures &amp; Algorithms</h3>
</div>
<div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
<span class="inline-flex items-center gap-1 font-semibold text-on-surface"><span class="material-symbols-outlined text-[16px] text-primary">style</span> 420 Cards</span>
<span class="text-outline-variant">•</span>
<span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">schedule</span> Due in 1 day</span>
</div>
</div>
</div>
<div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
<div class="flex flex-col gap-1.5 w-36 sm:w-44">
<div class="flex items-center justify-between text-label-sm font-semibold">
<span class="text-on-surface-variant">Accuracy</span>
<span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/80">92%</span>
</div>
<div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 92%"></div>
</div>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors">
<span class="material-symbols-outlined text-[18px]">visibility</span>
<span class="hidden sm:inline">Browse</span>
</button>
<button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs">
<span class="material-symbols-outlined text-[18px]">add</span>
<span class="hidden sm:inline">Add Card</span>
</button>
<button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options">
<span class="material-symbols-outlined text-[18px]">more_vert</span>
</button>
</div>
</div>
</div>
<!-- List Item 6: Create New Deck row -->
<div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-4 sm:p-5 flex items-center justify-between bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group">
<div class="flex items-center gap-4">
<div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform flex-shrink-0">
<span class="material-symbols-outlined text-[24px]">add_circle</span>
</div>
<div class="flex flex-col">
<h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">Create New Deck</h4>

</div>
</div>

</div>
</div>
</section>
</div>
</div>
</main>
<script>

        // View Toggle Controller (Grid vs List)
        const viewGridBtn = document.getElementById('view-grid-btn');
        const viewListBtn = document.getElementById('view-list-btn');
        const gridView = document.getElementById('decks-grid-view');
        const listView = document.getElementById('decks-list-view');

        if (viewGridBtn && viewListBtn && gridView && listView) {
            viewGridBtn.addEventListener('click', () => {
                // Switch to Grid View
                gridView.classList.remove('hidden');
                listView.classList.add('hidden');
                
                // Update Button Active States
                viewGridBtn.className = "flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all";
                viewGridBtn.setAttribute('aria-pressed', 'true');
                
                viewListBtn.className = "flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors";
                viewListBtn.setAttribute('aria-pressed', 'false');
            });

            viewListBtn.addEventListener('click', () => {
                // Switch to List View
                gridView.classList.add('hidden');
                listView.classList.remove('hidden');
                listView.classList.add('flex');
                
                // Update Button Active States
                viewListBtn.className = "flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all";
                viewListBtn.setAttribute('aria-pressed', 'true');
                
                viewGridBtn.className = "flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors";
                viewGridBtn.setAttribute('aria-pressed', 'false');
            });
        }

        // Simple smooth scroll controls for deck carousel
        const track = document.getElementById('deck-track');
        const scrollLeftBtn = document.getElementById('deck-scroll-left');
        const scrollRightBtn = document.getElementById('deck-scroll-right');

        if (track && scrollLeftBtn && scrollRightBtn) {
            scrollLeftBtn.addEventListener('click', () => {
                track.scrollBy({ left: -340, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', () => {
                track.scrollBy({ left: 340, behavior: 'smooth' });
            });
        }

        // Hero carousel controller
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        const heroPrev = document.getElementById('hero-prev');
        const heroNext = document.getElementById('hero-next');
        let currentSlide = 0;
        const totalSlides = slides.length;

        // Auto-play Configuration
        let autoPlayTimer = null;
        const SLIDE_DELAY = 5000; // 5 seconds interval time

        function showSlide(index) {
            currentSlide = (index + totalSlides) % totalSlides;
            slides.forEach((slide, idx) => {
                if (idx === currentSlide) {
                    slide.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
                    slide.classList.add('opacity-100', 'z-10');
                } else {
                    slide.classList.remove('opacity-100', 'z-10');
                    slide.classList.add('opacity-0', 'pointer-events-none', 'z-0');
                }
            });

            dots.forEach((dot, idx) => {
                if (idx === currentSlide) {
                    dot.classList.remove('bg-white/40', 'w-2.5');
                    dot.classList.add('bg-cyan-400', 'w-8');
                } else {
                    dot.classList.remove('bg-cyan-400', 'w-8');
                    dot.classList.add('bg-white/40', 'w-2.5');
                }
            });
        }

        // Starts or restarts the auto-advance timer
        function startAutoPlay() {
            stopAutoPlay(); // Safety clear
            autoPlayTimer = setInterval(() => {
                showSlide(currentSlide + 1);
            }, SLIDE_DELAY);
        }

        // Stops the timer loop cleanly
        function stopAutoPlay() {
            if (autoPlayTimer) {
                clearInterval(autoPlayTimer);
            }
        }

        // Navigation button behaviors with user interaction reset
        if (heroPrev && heroNext) {
            heroPrev.addEventListener('click', () => {
                showSlide(currentSlide - 1);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
            heroNext.addEventListener('click', () => {
                showSlide(currentSlide + 1);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
        }

        // Interactive pagination click event listeners
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                showSlide(idx);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
        });

        // Initialize the slide loop on page load
        startAutoPlay();
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>