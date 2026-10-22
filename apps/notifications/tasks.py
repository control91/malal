import logging

from celery import shared_task
from django.conf import settings
from django.core.mail import EmailMultiAlternatives

logger = logging.getLogger("apps.notifications")


@shared_task(bind=True, autoretry_for=(Exception,), retry_backoff=30, retry_kwargs={"max_retries": 4})
def send_email_task(self, subject: str, body: str, to: list[str], reply_to: list[str] | None = None, html: str | None = None):
    message = EmailMultiAlternatives(subject=subject, body=body, from_email=settings.DEFAULT_FROM_EMAIL, to=to, reply_to=reply_to or None)
    if html:
        message.attach_alternative(html, "text/html")
    message.send()
    logger.info("email sent to %s: %s", ", ".join(to), subject)
