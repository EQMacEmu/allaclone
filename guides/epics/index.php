<?php
require __DIR__ . '/_bootstrap.php';

$Title = 'Epic 1.0 Guides';
include($includes_dir . 'headers.php');

$catalog = epic_catalog();
?>
<div class="container epics epic-hub">
	<p class="epic-intro">
		Class Epic 1.0 guides for TAKP (classic 14 + Beastlord), with items, NPCs, zones, and factions linked into AllaClone.
		Steps are checked against the local quest scripts under <code>quests/</code>.
		All listed class guides are complete.
	</p>

	<table class="epic-table">
		<thead>
			<tr>
				<th>Class</th>
				<th>Epic 1.0</th>
				<th>Usefulness</th>
				<th>Guide</th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($catalog as $entry) :
	$rewards = isset($entry['item_ids']) ? $entry['item_ids'] : array($entry['item_id']);
	$rewardHtml = array();
	foreach ($rewards as $iid) {
		$rewardHtml[] = epic_item_link($db, $iid);
	}
	$ready = ($entry['status'] === 'ready');
?>
			<tr>
				<td><?php echo htmlspecialchars($entry['class']); ?></td>
				<td><?php echo implode(' / ', $rewardHtml); ?></td>
				<td><?php echo htmlspecialchars($entry['blurb']); ?></td>
				<td>
<?php if ($ready) : ?>
					<a href="<?php echo htmlspecialchars(epic_guide_url($entry['slug'])); ?>">Full guide</a>
<?php else : ?>
					<a href="<?php echo htmlspecialchars(epic_guide_url($entry['slug'])); ?>">Stub</a>
<?php endif; ?>
				</td>
			</tr>
<?php endforeach; ?>
		</tbody>
	</table>
</div>
</div>
<?php
include($includes_dir . 'footers.php');
