@extends('portal.layout.master')
@section("title","single-hotel")
@section('content')


@include('portal.single-hotel-page.rooms-section-title')
@include('portal.single-hotel-page.room-gallery')
@include('portal.single-hotel-page.room-amentites')
@include('portal.single-hotel-page.room-response')
@include('portal.single-hotel-page.single-room-column-area')
@include('portal.single-hotel-page.single-room-column-area-two')
@include('portal.single-hotel-page.about-property')
@include('portal.single-hotel-page.reviews')
@include('portal.single-hotel-page.amenities')
@include('portal.single-hotel-page.policies')


@endsection
