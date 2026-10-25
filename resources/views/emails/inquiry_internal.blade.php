Nouvelle demande reçue sur le site {!! $site->company_name !!}.

Référence    : {!! $inquiry->reference !!}
Type         : {!! $inquiry->kind->label() !!}
Nom          : {!! $inquiry->full_name !!}
Organisation : {!! $inquiry->organization ?: '—' !!}
Email        : {!! $inquiry->email !!}
Téléphone    : {!! $inquiry->phone ?: '—' !!}
@if ($inquiry->product)
Solution     : {!! $inquiry->product->name !!}
@endif
@if ($inquiry->category)
Domaine      : {!! $inquiry->category->name !!}
@endif

Objet : {!! $inquiry->subject !!}

{!! $inquiry->message !!}

Traiter la demande : {!! $inquiry->adminUrl() !!}
