@extends('statamic::layout')

@section('title', __('Edit Redirect'))

@section('content')
    <div class="flex items-center mb-3">
        <h1 class="flex-1">{{ __('Edit Redirect') }}</h1>
        <a href="{{ cp_route('abra-statamic-redirects.index') }}" class="btn">{{ __('Back to List') }}</a>
    </div>

    <div class="card p-4">
        @include('abra-redirects::components.form', [
            'action' => cp_route('abra-statamic-redirects.update', $redirect['id']),
            'method' => 'PATCH',
            'redirect' => $redirect,
            'submitButtonText' => __('Update Redirect')
        ])
    </div>
@endsection
