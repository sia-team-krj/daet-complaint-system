@extends('errors.layout')

@section('code', '401')
@section('title', __('errors.401.title'))
@section('message', __('errors.401.message'))
@section('action_url', route('login'))
@section('action_label', __('errors.action_sign_in'))
@section('secondary', 'back')
