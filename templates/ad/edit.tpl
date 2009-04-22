{START_FORM}
<table class="form-table" width="99%">
  <tr>
    <td>{TITLE_LABEL}</td>
    <td>{TITLE}
    <!-- BEGIN title-error -->
    <div class="ads-error">{TITLE_ERROR}</div>
    <!-- END title-error -->
    </td>
  </tr>
  <!-- BEGIN filename -->
  <tr>
    <td>{FILENAME_LABEL}</td>
    <td>{FILENAME}
    <!-- BEGIN filename-error -->
    <div class="ads-error">{FILENAME_ERROR}</div>
    <!-- END filename-error -->
    </td>
  </tr>
  <!-- END filename -->
  <!-- BEGIN ad-text -->
  <tr>
    <td>{AD_TEXT_LABEL}</td>
    <td>{AD_TEXT}
    <!-- BEGIN ad-text-error -->
    <div class="ads-error">{AD_TEXT_ERROR}</div>
    <!-- END ad-text-error -->
    </td>
  </tr>
  <!-- END ad-text -->
  <tr>
    <td>{URL_LABEL}</td>
    <td>http://{URL}</td>
  </tr>
</table>
{SUBMIT}
{END_FORM}
