@extends('layouts.app')

@section('content')
    <div class="container mt-2">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Available rooms</div>

                    <div class="card-body">
                        <chat-component></chat-component>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
