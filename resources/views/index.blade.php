@extends('statamic::layout')

@section('title', __('Redirects'))

@section('content')
    <div class="flex items-center mb-3">
        <h1 class="flex-1">{{ __('Redirects') }}</h1>
        <a href="{{ cp_route('abra-statamic-redirects.create') }}" class="btn-primary">{{ __('Add Redirect') }}</a>
    </div>

    <div class="card p-0">
        <table class="data-table">
            <thead>
            <tr>
                <th>{{ __('Source') }}</th>
                <th>{{ __('Destination') }}</th>
                <th>{{ __('Status Code') }}</th>
                <th class="actions-column"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($redirects as $redirect)
                <tr>
                    <td>{{ $redirect['source'] }}</td>
                    <td>{{ $redirect['destination'] }}</td>
                    <td>{{ $redirect['status_code'] }}</td>
                    <td class="flex justify-end">
                        <div class="btn-group">
                            <a href="{{ cp_route('abra-statamic-redirects.edit', $redirect['id']) }}" class="btn">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ cp_route('abra-statamic-redirects.destroy', $redirect['id']) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger ml-2" onclick="return confirm('{{ __('Are you sure you want to delete this redirect?') }}')">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center p-3">{{ __('No redirects found.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
