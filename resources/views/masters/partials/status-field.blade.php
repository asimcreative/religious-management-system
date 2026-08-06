{{--
    Shared active/inactive selector for master data.

    Master tables store status as 1/0 rather than the full Status enum used by
    business records, so the options are declared explicitly here — once —
    instead of in seven separate forms.
--}}
<x-form.select
    name="status"
    :label="__('masters.status')"
    :selected="$record?->status?->value ?? 1"
    :options="[1 => __('masters.active'), 0 => __('masters.inactive')]"
    :help="__('masters.status_help')"
    required />
