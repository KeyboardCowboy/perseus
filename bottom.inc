          <!--stopindex-->
          <div id="printable">
            <img src="http://nreldevy.nrel.gov/images/icon_print.gif" width="14" height="10" alt="" /> <a href="?print">Printable Version</a>
          </div>
          <!--startindex-->
        </div>
        <!--stopindex-->
        <div id="footer">
          <div align="center"><img src="http://nreldevy.nrel.gov/images/foot_nrelis.gif" alt="NREL is a national laboratory of the U.S. Department of Energy, Office of Energy Efficiency and Renewable Energy, operated by the Alliance for Sustainable Energy, LLC" usemap="#footer_map" /></div>
          <map name="footer_map" id="footer_map">
            <area shape="rect" coords="27,7,61,20" href="http://www.nrel.gov/" alt="NREL" />
            <area shape="rect" coords="238,7,399,19" href="http://www.energy.gov/" alt="U.S. Department of Energy" />
            <area shape="rect" coords="405,7,697,21" href="http://www.eere.energy.gov/" alt="Office of Energy Efficiency and Renewable Energy" />
            <area shape="rect" coords="307,21,524,35" href="http://www.allianceforsustainableenergy.org/" alt="Alliance for Sustainable Energy, LLC" />
          </map>
          <p class="date">Content Last Updated: October 25, 2013 </p>
          <p>
            <a href="http://nreldevy.nrel.gov/biomass/webmaster.html">Need Help?</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="http://nreldevy.nrel.gov/security.html">Security &amp; Privacy</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="http://nreldevy.nrel.gov/disclaimer.html">Disclaimer</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="http://nreldevy.nrel.gov/">NREL Home</a></p>
          <p class="poweredby">This Web site is <a href="http://nreldevy.nrel.gov/poweredbyrenewables.html">powered by renewable energy</a>.</p>
          <br />
        </div>
        <!--startindex-->
      </div>
      <script language="javascript" type="text/javascript">

        $(document).ready(function() {
          //
          $("#country").change(function() {
            if ($(this).val() != "United States") {
              $("#citizen").prop("checked", false);
            } else {
              $("#citizen").prop("checked", true);
            }
          });


          var timeId = $("input[name='timeslot']").filter(":checked").val();
          if (timeId != undefined) {
            var timeSpan = $("label[for='timeslot" + timeId + "']").text();
            $("#Tour_TimeSlot").val(timeSpan);
          }

          //console.log($.fn.jquery);
          $("input[name|='timeslot']").change(function() {
            var timeId = $("input[name='timeslot']").filter(":checked").val();
            var timeSpan = $("label[for='timeslot" + timeId + "']").text();
            $("#Tour_TimeSlot").val(timeSpan);
            //console.log("Time ID: " + timeId);
            var meetingSuggest = parseInt(timeId) + 1;
            //console.log("meetingSuggest ID: " + meetingSuggest);
            if ($("input[name='meetslot']").filter(":checked").val() == undefined) {
              $("#meetslot" + meetingSuggest).prop("checked", true);
              $("#Meet_TimeSlot").val($("label[for='meetslot" + meetingSuggest + "']").text());
            }
          });

          //One-on-One Meetings
          var meetId = $("input[name='meetslot']").filter(":checked").val();
          //console.log("Meet ID: " + meetId);
          if (meetId != undefined) {
            var timeSpan = $("label[for='meetslot" + meetId + "']").text();
            $("#Meet_TimeSlot").val(timeSpan);
          }
          $("input[name='meetslot']").change(function() {
            var meetId = $("input[name='meetslot']").filter(":checked").val();
            var timeSpan = $("label[for='meetslot" + meetId + "']").text();
            //console.log("Time id: " + meetId);
            //console.log("Time span: " + timeSpan);
            $("#Meet_TimeSlot").val(timeSpan);
          });
          //console.log("Time span: " + $("#Meet_TimeSlot").val());

        });
      </script>
    </body>
  </html>
