@extends('statamic::layout')

@section('title', 'Redirects')

@section('content')
    <div class="flex items-center mb-3">
        <h1 class="flex-1">Redirects</h1>
        <a href="{{ cp_route('abra-statamic-redirects.create') }}" class="btn-primary">Add Redirect</a>
    </div>

    <div class="card p-0">
        <table class="data-table">
            <thead>
            <tr>
                <th>Source</th>
                <th>Destination</th>
                <th>Status Code</th>
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
                            <a href="{{ cp_route('abra-statamic-redirects.edit', $redirect['id']) }}" class="btn">Edit</a>
                            <form method="POST" action="{{ cp_route('abra-statamic-redirects.destroy', $redirect['id']) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger ml-2" onclick="return confirm('Are you sure you want to delete this redirect?')">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center p-3">No redirects found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
