@extends('layouts.app')

@section('title', 'All Movies · moviesanalyzer')

@section('content')
    <livewire:movies.library-explorer-panel :initial-search="$search" />
@endsection
