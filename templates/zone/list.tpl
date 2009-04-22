<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th>{TITLE} {TITLE_SORT}</th>
    <th>{DESCRIPTION}</th>
    <th>{AD_TYPE} {AD_TYPE_SORT}</th>
    <th>{MAX_NUM_ADS} {MAX_NUM_ADS_SORT}</th>
    <th>{ACTIVE} {ACTIVE_SORT}</th>
    <th>{ACTION}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{TITLE}</td>
    <td>{DESCRIPTION}</td>
    <td>{AD_TYPE}</td>
    <td>{MAX_NUM_ADS}</td>
    <td>{ACTIVE}</td>
    <td>{ACTION}</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="6">{EMPTY_MESSAGE}</td>
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
