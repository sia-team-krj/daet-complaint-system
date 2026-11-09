@extends('errors.layout')

@php
    $status = isset($exception) && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        ? $exception->getStatusCode()
        : 500;
@endphp

@section('code', (string) $status)
@section('title', __('errors.5xx.title'))
@section('message', __('errors.5xx.message'))
@section('secondary', 'retry')
