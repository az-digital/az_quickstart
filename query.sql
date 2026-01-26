SELECT "node__field_az_application_date"."field_az_application_date_value" AS "node__field_az_application_date_field_az_application_date_va", "node_field_data"."title" AS "node_field_data_title", "node_field_data"."nid" AS "nid"
FROM
{node_field_data} "node_field_data"
LEFT JOIN {node__field_az_ongoing} "node__field_az_ongoing" ON node_field_data.nid = node__field_az_ongoing.entity_id AND node__field_az_ongoing.deleted = '0'
LEFT JOIN {node__field_az_application_date} "node__field_az_application_date" ON node_field_data.nid = node__field_az_application_date.entity_id AND node__field_az_application_date.deleted = '0'
WHERE (("node_field_data"."status" = '1') AND ("node_field_data"."type" IN ('az_opportunity'))) AND ("node__field_az_ongoing"."field_az_ongoing_value" = '1') AND ((DATE_FORMAT((node__field_az_application_date.field_az_application_date_end_value + INTERVAL -25200 SECOND), '%Y-%m-%d') >= DATE_FORMAT('2026-01-23T15:15:00', '%Y-%m-%d')))
ORDER BY "node__field_az_application_date_field_az_application_date_va" ASC, "node_field_data_title" ASC
LIMIT 11 OFFSET 0
