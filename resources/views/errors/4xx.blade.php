@extends('errors.layout')

@php
    $status = isset($exception) && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        ? $exception->getStatusCode()
        : 400;
@endphp

@section('code', (string) $status)
@section('title', __('errors.4xx.title'))
@section('message', __('errors.4xx.message'))
@section('secondary', 'back')
