<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th>{NAME} {TITLE_SORT}</th>
    <th>{TYPE} {AD_TYPE_SORT}</th>
    <th>{ACTIVE} {ACTIVE_SORT}</th>
    <th>{APPROVED} {APPROVED_SORT}</th>
    <th>{ACTION}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{NAME}</td>
    <td>{TYPE}</td>
    <td>{ACTIVE}</td>
    <td>{APPROVED}</td>
    <td>{ACTION}</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="5">{EMPTY_MESSAGE}</td>
  </tr>
<!-- END empty_message -->
</table>

<!-- BEGIN navigation -->
<div class="align-center">
{TOTAL_ROWS}<br />
{PAGE_LABEL} {PAGES}<br />
{LIMIT_LABEL} {LIMITS}
</div>
<!-- END navigation -->
<!-- BEGIN search -->
<div class="align-right">
{SEARCH}
</div>
<!-- END search -->

{ADD_NEW}: {BANNER_LINK} | {TEXT_AD_LINK}