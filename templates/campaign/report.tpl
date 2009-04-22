<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th colspan="7">{CAMPAIGN}</th>
  </tr>
  <tr>
    <th>{NAME} {TITLE_SORT}</th>
    <th>{TYPE} {AD_TYPE_SORT}</th>
    <th>{ACTIVE} {ACTIVE_SORT}</th>
    <th>{APPROVED} {APPROVED_SORT}</th>
    <th>{VIEWS}</th>
    <th>{HITS}</th>
    <th>{CTR}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{NAME}</td>
    <td>{TYPE}</td>
    <td>{ACTIVE}</td>
    <td>{APPROVED}</td>
    <td>{VIEWS}</td>
    <td>{HITS}</td>
    <td>{CTR}%</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="7">{EMPTY_MESSAGE}</td>
  </tr>
<!-- END empty_message -->
</table>
<br />
