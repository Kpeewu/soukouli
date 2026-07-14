@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Gestion des Utilisateurs</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Utilisateurs</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        @if ($notification = Session::get('notification'))
            <div class="alert alert-{{ $notification['type'] }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <p class="mb-0">{{ $notification['message'] }}</p>
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Liste des Utilisateurs</h3>
                <a href="{{ route('users.create') }}" class="btn btn-success">Ajouter un utilisateur</a>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                        <thead>
                            <tr>
                                <th style="width: 80px;">N°</th>
                                <th class="text-center">Nom d'utilisateur</th>
                                <th class="text-center">Role</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $user)
                                <tr>
                                    <td class="text-center text-primary" style="font-weight: 700;">{{ $index + 1 }}</td>
                                    <td class="text-center">{{ $user->username }}</td>
                                    <td class="text-center">
                                        @foreach($user->roles as $role)
                                            <span class="badge badge-{{ $role->name === 'admin' ? 'danger' : 'primary' }}">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-around">
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-success">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            @if($user->username !== 'monavenir')
                                                <form action="{{ route('users.destroy', $user) }}" method="post" onsubmit="return confirm('Voulez-vous supprimer cet utilisateur ?')">
                                                    @csrf
                                                    @method('delete')
                                                    <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="4">Aucun utilisateur</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
