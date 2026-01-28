@extends('layouts.app')

@section('content')
<div id="profile-app" data-token="{{ $token }}"></div>
@vite('resources/js/main.jsx')
@endsection
