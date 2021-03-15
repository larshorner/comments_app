@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <div class="row" style="padding-top: 10px">
        <div class="col-12">
            <form name="login" method="post" action="{{ route('login') }}">
                @csrf
                <div class="card">
                    <div class="card-header">Login</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input id="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary">Login</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
