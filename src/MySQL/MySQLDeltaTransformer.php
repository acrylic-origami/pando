<?hh // decl
namespace Pando\MySQL;
class MySQLDeltaTransformer<TFrom as Iterable<MySQLDelta>> implements DatabaseDeltaTransformer<MySQLDelta, TFrom, Affected\MySQLIdentifierTree> {
	public function __construct(
		protected string $default_db,
		protected Affected\MySQLIdentifierTreeBuilder $identifier_builder
		) {}
	public function transform(TFrom $v): Affected\MySQLIdentifierTree {
		$affecteds = new Affected\MySQLIdentifierTree($this->default_db);
		foreach($v as $delta_obj) {
			// produce affected and target
			// [OBSOLETE] we have to play nice with the returned values from the parser: begrudgingly work with the array<mixed> $delt
			// Not anymore: the deltas are Trees now!
			$delta = $delta_obj['deltas'];
			switch(key($delta)) {
				//MULTI-TABLE, VARIABLE-COLUMN SIDE EFFECTS
				case 'UPDATE':
					$aliases = Map{}; // Map<string, \Pando\Affected\MySQLIdentifierTree>
					
					foreach(current($delta) as $target_table) {
						// each of these tables may or may not have been updated. Should we label them with catchall or empty? I guess it depends on how ambiguous the column are... this is really only of use for the screening, which should be overenthusiastic rather than returning false negatives... Catchall might be a safer solution should no specific entries exist already
						// the way I'm going to implement it is going to be to leave it blank for the timebeing, then if nothing is added by the end, switch it to catchall
						
						$parts = $target_table['no_quotes']['parts'];
						list($table, $db) = array_reverse($parts);
						$identifier = $this->identifier_builder->scaffold(null, $table, $db); // $db nullable
						// if(!$affecteds->is_subset($identifier->build($identifier::CATCHALL))) {
							
						// }
						
						if(array_key_exists('alias', $target_table) && is_array($target_table['alias'])) {
							$aliases[$target_table['alias']['name']] = $identifier; // does nothing if there is a default column on the identifier_builder (since it uses null-coalescence on $this->col); watch out for this
						}
						// if($affecteds->has_partial_intersect($identifier)) {
						// }
						$affecteds->union($identifier->build()); // try to add this table to the identifier
					}
					if(array_key_exists('SET', $delta)) {
						$SET = $delta['SET'];
						invariant($SET instanceof Traversable, 'MySQL parser has become inconsistent with this implementation of delta transformer.');
						foreach($SET as $expr) {
							list($col, $table, $db) = array_reverse($expr['sub_tree'][0]['no_quotes']['parts']);
							if($aliases->containsKey($table)) {
								$affecteds->union($aliases[$table]->build($col));
							}
							else {
								$affecteds->union($this->identifier_builder->build($col, $table, $db)); // assume ambiguity checks are already built into the union operation (NEED TO CHECK!)
							}
						}
					}
					break;
				
				//SINGLE-TABLE, ALL-COLUMN SIDE EFFECTS
				case 'INSERT':
				// FALLTHROUGH
				case 'REPLACE':
					$colref = end(current($delta));
					list($table, $db) = array_reverse($colref);
					$identifier_builder = $this->identifier_builder;
					$affecteds->union($this->identifier_builder->build($identifier_builder::CATCHALL, $table, $db));
					break;
				case 'DELETE':
					invariant(array_key_exists('FROM', $delta), 'MySQL parser has become inconsistent with this implementation of delta transformer.');
					$FROM = $delta['FROM'];
					invariant(array_key_exists(0, $FROM) && is_array($FROM[0]), 'MySQL parser has become inconsistent with this implementation of delta transformer.');
					list($table, $db) = array_reverse($delta['FROM'][0]);
					$identifier_builder = $this->identifier_builder;
					$affecteds->union($this->identifier_builder->build($identifier_builder::CATCHALL, $table, $db));
					break;
				
				//User-defined variable side-effects
				case 'SELECT':
				// FALLTHROUGH
				case 'SET':
					// all queries will be checked for bracket_expressions outside the switch-case
					break;
				
				//You fucked up
				case 'DROP':
					//uh...
					throw new \UnexpectedValueException('I don\'t think you _really_ wanted to drop anything. Well... shit.');
					break;
			}
			$affecteds->union($this->_find_side_effect_accumulators($delta_obj));
		}
		return $affecteds;
	
	}
	
	public function _find_side_effect_accumulators(DatabaseDelta $delta_obj): \Pando\Affected\MySQLIdentifierTree {
		$accumulators = new \Pando\Affected\MySQLIdentifierTree($this->default_db);
		$delta = $delta_obj['delta'];
		if($delta['expr_type'] === 'bracket_expression') {
			$accumulators->add_SEA($delta['sub_tree'][0]['base_expr']);
			$accumulators->union($this->_find_side_effect_accumulators(shape('database' => $delta_obj['database'], 'delta' => $delta['sub_tree'][2])));
		}
		else {  
			foreach($delta as $key=>$value) {
				if(is_array($value)) {
				$accumulators->union($this->_find_side_effect_accumulators($value));
				}
			}
		}
		return $accumulators;
	}
}