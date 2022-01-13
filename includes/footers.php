        <footer class="footer">
        	<p>EverQuest is a registered trademark of Daybreak Game Company LLC.</p>
        	<p>EQEmulator or The Al`kabor Project is not associated or affiliated in any way with Daybreak Game Company LLC.</p>
        </footer>
        </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
        <script src="<?php echo $includes_url; ?>js/jquery.floatHead.js"></script>
        <script>
        	$(document).ready(function() {
        		$(".sticky-header").floatThead({
        			responsiveContainer: function($table) {
        				return $table.closest('.table-wrapper');
        			}
        		});

        		$(".refine-search").on('click', function(el) {
        			el.preventDefault();
        			$('.search-wrapper, .refine-search').toggleClass('visible');
        		})

        		if (window.location.href.indexOf("?iname=") > -1) {
        			$('.visible').removeClass('visible');
        		}

        		if (window.location.href.indexOf("items.php") > -1) {

        			$('select[name=iminlevel] option:lt(1)').html('Min Lvl');
        			$('select[name=ireqlevel] option:lt(1)').html('Req Lvl');
        			$('select[name=iavaillevel] option:lt(1)').html('Available Lvl');

        			var noDrop = $('input[name=inodrop]');
        			var label = $('label[for=inodrop]');

        			$(label).on('click', function() {
        				$(noDrop).trigger('click');
        			})
        		}

        	});
        </script>
        </body>

        </html>