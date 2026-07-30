@extends('layouts.app')
@section('content')
<section class="page-head">
    <div><p class="eyebrow">Admin accounts</p><h1>Add Account</h1></div>
    <a class="button muted" href="{{ route('admin.accounts.index') }}"><i data-lucide="arrow-right"></i>Back</a>
</section>

@include('admin.accounts._form')
@endsection
