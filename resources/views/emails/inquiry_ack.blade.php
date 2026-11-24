Bonjour {!! $inquiry->first_name !!},

Nous avons bien reçu votre demande « {!! $inquiry->subject !!} » (référence {!! $inquiry->reference !!}).
Un membre de notre équipe l'étudie et reviendra vers vous.

Ceci est un message automatique : pour compléter votre demande, écrivez à {!! $site->email_contact !!} en rappelant votre référence.

Cordialement,
L'équipe {!! $site->company_name !!}
