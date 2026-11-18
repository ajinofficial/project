<style>
.service-orders-table th,.service-orders-table td,.service-costs-table th,.service-costs-table td{white-space:nowrap;vertical-align:middle}
.service-form [hidden]{display:none!important}
.service-listing-results{position:relative;min-height:160px}.service-listing-results .product-listing-loader{inset:0}.service-listing-results.is-loading .product-listing-loader{display:grid}.service-listing-results.is-loading [data-service-results]{opacity:.38;pointer-events:none}
.service-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px}.service-header h1{margin:4px 0}.service-header p{color:#667085}.service-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}.service-stats article{background:#fff;border:1px solid #e4e7ec;border-radius:14px;padding:20px}.service-stats strong{display:block;font-size:24px;margin-top:8px}.service-layout{display:grid;grid-template-columns:minmax(280px,360px) minmax(0,1fr);gap:24px}.service-layout .admin-section{min-width:0}.service-form{display:grid;gap:16px}.service-form label{display:grid;gap:7px;font-weight:600}.service-form input,.service-form select,.service-form textarea,.service-filter input,.service-filter select{width:100%;padding:11px;border:1px solid #d0d5dd;border-radius:8px;background:#fff;color:#101828;font:inherit;box-sizing:border-box}.service-form button{cursor:pointer}.service-filter{display:flex;gap:12px;margin-bottom:20px}.service-filter input{flex:1}.service-filter select{width:auto}.service-note{padding:14px;border-radius:8px;background:#eff6ff;margin-bottom:20px}.service-panels{display:grid;gap:24px}.service-status{display:inline-block;padding:5px 9px;background:#eff6ff;color:#175cd3;border-radius:20px;white-space:nowrap}.service-help{color:#667085;font-size:13px}.service-form fieldset{border:0;padding:0;margin:0}.service-form small{color:#b42318}@media(max-width:1000px){.service-layout{grid-template-columns:1fr}.service-stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.service-header,.service-filter{flex-direction:column;align-items:stretch}.service-stats{grid-template-columns:1fr 1fr}.service-stats article{padding:12px}}
.service-header{padding:24px;background:#fff;border:1px solid #e4e7ec;border-radius:16px;align-items:center}
.service-header>div{min-width:0}
.service-header .eyebrow{margin:0 0 8px;color:#175cd3;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.service-header h1{margin:0;color:#101828;font-size:30px;line-height:1.25;overflow-wrap:anywhere}
.service-header p:not(.eyebrow){margin:10px 0 0;font-size:14px;line-height:1.6}
.service-header>a{flex-shrink:0;display:inline-flex;justify-content:center;align-items:center;min-height:42px;box-sizing:border-box;white-space:nowrap}
.service-stats{grid-template-columns:repeat(4,minmax(0,1fr))}
.service-stats article{min-width:0;padding:20px;border-top:3px solid #dbeafe;color:#667085;font-size:13px;font-weight:500;line-height:1.5;box-shadow:0 2px 6px #10182805}
.service-stats article:first-child{border-top-color:#60a5fa}
.service-stats article:nth-child(2){border-top-color:#34d399}
.service-stats article:nth-child(3){border-top-color:#a78bfa}
.service-stats article:nth-child(4){border-top-color:#fbbf24}
.service-stats strong{color:#101828;font-size:clamp(18px,2vw,26px);font-weight:700;font-variant-numeric:tabular-nums;overflow-wrap:anywhere;line-height:1.3;margin-top:10px}
.service-customer-field{display:grid;gap:10px}
.service-add-customer{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 14px;border:1px solid #b2ccff;border-radius:8px;background:#eff6ff;color:#175cd3;text-decoration:none;font-size:13px;font-weight:600;min-height:40px;box-sizing:border-box;transition:background .15s,border-color .15s}
.service-add-customer span{font-size:19px;line-height:1}
.service-add-customer:hover{background:#dbeafe;border-color:#84adff;color:#1849a9}
.service-add-customer:focus-visible{outline:3px solid #84adff;outline-offset:3px}
#new-service{scroll-margin-top:24px}
@media(max-width:1000px){.service-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.service-header{padding:18px;gap:18px}.service-header h1{font-size:26px}.service-stats{gap:10px}.service-stats article{padding:14px;font-size:12px}.service-stats strong{font-size:20px}}
@media(max-width:360px){.service-stats{grid-template-columns:minmax(0,1fr)}}
</style>
@if ($errors->any())<div class="error-summary" role="alert"><strong>{{ __('common.please_check_the_details_1ecd84') }}</strong>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
