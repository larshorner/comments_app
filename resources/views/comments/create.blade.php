@extends('layouts.app')

@section('title', 'Page Title')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')
    <div class="row">
        <div class="col-3"></div>
        <div class="col-6">
            <form method="POST" action="{{ url()->current() }}">
                @csrf
                <div class="form-group">
                    <label for="comment-title">Title</label>
                    <input type="text" id="comment-title" name="title" class="form-control">
                </div>

                <div class="form-group">
                    <label for="comment-text">Comment</label>
                    <textarea id="comment-text" name="text" class="form-control" rows="6"></textarea>
                </div>
                <button class="btn btn-primary">
                    {{ __('Create') }}
                </button>
            </form>
        </div>
    </div>
@endsection
