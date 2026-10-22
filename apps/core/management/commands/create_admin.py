"""
Secure super-administrator bootstrap.

    python manage.py create_admin --email admin@entreprise.gn
    DJANGO_ADMIN_PASSWORD=... python manage.py create_admin --email admin@entreprise.gn --noinput

The password is never accepted as a command-line argument (it would leak in
shell history and process lists). It is prompted for, or read from the
DJANGO_ADMIN_PASSWORD environment variable in non-interactive mode, and is
checked against the password validators.
"""

import getpass
import os

from django.contrib.auth import get_user_model, password_validation
from django.core.exceptions import ValidationError
from django.core.management.base import BaseCommand, CommandError
from django.core.validators import validate_email

from apps.accounts.roles import Role


class Command(BaseCommand):
    help = "Crée (ou met à jour) un compte super administrateur de façon sécurisée."

    def add_arguments(self, parser):
        parser.add_argument("--email", required=True)
        parser.add_argument("--first-name", default="")
        parser.add_argument("--last-name", default="")
        parser.add_argument("--noinput", action="store_true", help="Lit le mot de passe dans DJANGO_ADMIN_PASSWORD.")
        parser.add_argument("--update-password", action="store_true", help="Autorise la modification d'un compte existant.")

    def handle(self, *args, **options):
        User = get_user_model()
        email = options["email"].strip().lower()
        try:
            validate_email(email)
        except ValidationError as exc:
            raise CommandError("Adresse email invalide.") from exc

        existing = User.objects.filter(email=email).first()
        if existing and not options["update_password"]:
            raise CommandError("Ce compte existe déjà. Utilisez --update-password pour changer son mot de passe.")

        password = self._read_password(options["noinput"])
        candidate = existing or User(email=email, first_name=options["first_name"], last_name=options["last_name"])
        try:
            password_validation.validate_password(password, candidate)
        except ValidationError as exc:
            raise CommandError("Mot de passe refusé : " + " ".join(exc.messages)) from exc

        if existing:
            existing.set_password(password)
            existing.is_active = True
            existing.is_staff = True
            existing.is_superuser = True
            existing.role = Role.SUPERADMIN
            existing.save()
            self.stdout.write(self.style.SUCCESS(f"Mot de passe mis à jour pour {email}."))
        else:
            User.objects.create_superuser(email=email, password=password, first_name=options["first_name"], last_name=options["last_name"])
            self.stdout.write(self.style.SUCCESS(f"Super administrateur {email} créé."))

    def _read_password(self, noinput: bool) -> str:
        if noinput:
            password = os.environ.get("DJANGO_ADMIN_PASSWORD", "")
            if not password:
                raise CommandError("DJANGO_ADMIN_PASSWORD doit être défini avec --noinput.")
            return password
        first = getpass.getpass("Mot de passe : ")
        second = getpass.getpass("Confirmation : ")
        if first != second:
            raise CommandError("Les deux saisies ne correspondent pas.")
        if not first:
            raise CommandError("Mot de passe vide.")
        return first
