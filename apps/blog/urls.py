from django.urls import path

from . import views

app_name = "blog"

urlpatterns = [
    path("blog/", views.article_list, name="list"),
    path("blog/categorie/<slug:slug>/", views.category, name="category"),
    path("blog/etiquette/<slug:slug>/", views.tag, name="tag"),
    path("blog/<slug:slug>/", views.article_detail, name="detail"),
]
