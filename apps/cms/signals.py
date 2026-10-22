from django.core.cache import cache
from django.db.models.signals import post_delete, post_save
from django.dispatch import receiver

from .models import MENUS_CACHE_KEY, NavigationItem, NavigationMenu


@receiver([post_save, post_delete], sender=NavigationItem)
@receiver([post_save, post_delete], sender=NavigationMenu)
def invalidate_menus(**kwargs):
    cache.delete(MENUS_CACHE_KEY)
