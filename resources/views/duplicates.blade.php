@extends('layouts.app')

@section('title', 'moviesanalyzer Duplicates')

@section('content')
    <livewire:duplicates.conflict-center-panel :initial-sort="$sort" />
@endsection
