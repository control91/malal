from django import forms


class RichTextWidget(forms.Textarea):
    """Trix editor bound to a hidden textarea (assets loaded by the dashboard layout)."""

    template_name = "dashboard/widgets/richtext.html"


class IconSelectWidget(forms.Select):
    template_name = "dashboard/widgets/icon_select.html"


class ColorWidget(forms.TextInput):
    input_type = "color"
