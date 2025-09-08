@extends('statamic::layout')

@section('title', 'Create Redirect')

@section('content')
    <div class="flex items-center mb-3">
        <h1 class="flex-1">Create Redirect</h1>
        <a href="{{ cp_route('abra-redirects.index') }}" class="btn">Back to List</a>
    </div>

    <div class="card p-4">
        @include('abra-redirects::components.form', [
            'action' => cp_route('abra-redirects.store'),
            'method' => 'POST',
            'redirect' => null,
            'submitButtonText' => 'Create Redirect'
        ])
    </div>
@endsection
