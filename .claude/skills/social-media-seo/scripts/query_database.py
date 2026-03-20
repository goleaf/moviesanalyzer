#!/usr/bin/env python3
"""
Social Media SEO Database Query Helper

Simple utility to search and filter CSV databases programmatically.
Usage:
    python query_database.py caption-styles --platform instagram --goal educate
    python query_database.py hook-formulas --emotion curiosity --min-conversion 5.0
    python query_database.py hashtag-strategies --platform twitter
"""

import csv
import sys
from pathlib import Path
from typing import List, Dict, Any


class DatabaseQuery:
    """Query CSV databases with filtering capabilities."""
    
    def __init__(self, db_path: Path):
        self.db_path = db_path
        self.data = []
        self._load_data()
    
    def _load_data(self):
        """Load CSV data into memory."""
        with open(self.db_path, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            self.data = list(reader)
    
    def filter(self, **kwargs) -> List[Dict[str, Any]]:
        """
        Filter data by column values.
        
        Args:
            **kwargs: Column name and value pairs to filter by
                     Use _min or _max suffix for numeric comparisons
        
        Returns:
            List of matching rows
        """
        results = self.data.copy()
        
        for key, value in kwargs.items():
            # Handle min/max numeric filters
            if key.endswith('_min'):
                col = key.replace('_min', '')
                results = [r for r in results if self._to_number(r.get(col, '0')) >= value]
            elif key.endswith('_max'):
                col = key.replace('_max', '')
                results = [r for r in results if self._to_number(r.get(col, '0')) <= value]
            # Handle string filters (case-insensitive partial match)
            else:
                results = [r for r in results if value.lower() in r.get(key, '').lower()]
        
        return results
    
    def sort(self, results: List[Dict], by: str, reverse: bool = False) -> List[Dict]:
        """Sort results by a column."""
        return sorted(results, key=lambda x: self._to_number(x.get(by, '0')), reverse=reverse)
    
    @staticmethod
    def _to_number(value: str) -> float:
        """Convert string to number, handling percentages."""
        try:
            # Handle percentage strings like "8.2%"
            if '%' in value:
                return float(value.replace('%', ''))
            return float(value)
        except ValueError:
            return 0.0
    
    def display(self, results: List[Dict], columns: List[str] = None, limit: int = None):
        """Display results in readable format."""
        if not results:
            print("No results found.")
            return
        
        if limit:
            results = results[:limit]
        
        # Use specified columns or all columns
        cols = columns or list(results[0].keys())
        
        print(f"\n{'='*80}")
        print(f"Found {len(results)} results")
        print(f"{'='*80}\n")
        
        for i, row in enumerate(results, 1):
            print(f"Result #{i}:")
            for col in cols:
                if col in row and row[col]:
                    print(f"  {col}: {row[col]}")
            print()


def main():
    """CLI interface for database queries."""
    if len(sys.argv) < 2:
        print("Usage: python query_database.py <database-name> [--filter key=value] [--sort column] [--limit N]")
        print("\nAvailable databases:")
        print("  caption-styles")
        print("  hook-formulas")
        print("  thread-structures")
        print("  hashtag-strategies")
        print("  viral-patterns")
        print("  engagement-tactics")
        print("  keyword-clusters")
        print("\nExamples:")
        print("  python query_database.py caption-styles --platform instagram --goal educate")
        print("  python query_database.py hook-formulas --emotion curiosity --conversion_min 5.0")
        print("  python query_database.py hashtag-strategies --platform twitter --sort avg_reach_boost")
        sys.exit(1)
    
    # Parse and validate database name (prevent path traversal)
    db_name = sys.argv[1]
    
    # Whitelist of valid database names
    valid_databases = {
        'caption-styles', 'hook-formulas', 'thread-structures',
        'hashtag-strategies', 'viral-patterns', 'engagement-tactics',
        'keyword-clusters'
    }
    
    if db_name not in valid_databases:
        print(f"Error: Invalid database name '{db_name}'")
        print(f"Valid databases: {', '.join(sorted(valid_databases))}")
        sys.exit(1)
    
    # Construct safe path
    script_dir = Path(__file__).resolve().parent
    db_path = (script_dir.parent / 'databases' / f'{db_name}.csv').resolve()
    
    # Verify path is within databases directory (prevent traversal)
    databases_dir = (script_dir.parent / 'databases').resolve()
    if not db_path.is_relative_to(databases_dir):
        print(f"Error: Path traversal detected")
        sys.exit(1)
    
    if not db_path.exists():
        print(f"Error: Database '{db_name}' not found at {db_path}")
        sys.exit(1)
    
    # Initialize query
    query = DatabaseQuery(db_path)
    
    # Parse filters
    filters = {}
    sort_by = None
    limit = None
    
    i = 2
    while i < len(sys.argv):
        arg = sys.argv[i]
        
        if arg == '--sort' and i + 1 < len(sys.argv):
            sort_by = sys.argv[i + 1]
            i += 2
        elif arg == '--limit' and i + 1 < len(sys.argv):
            limit = int(sys.argv[i + 1])
            i += 2
        elif '=' in arg:
            key, value = arg.split('=', 1)
            key = key.lstrip('--')
            # Try to convert to number if it looks numeric
            try:
                if '.' in value:
                    value = float(value)
                elif value.isdigit():
                    value = int(value)
            except ValueError:
                pass
            filters[key] = value
            i += 1
        else:
            i += 1
    
    # Execute query
    results = query.filter(**filters)
    
    # Sort if requested
    if sort_by:
        results = query.sort(results, by=sort_by, reverse=True)
    
    # Display results
    query.display(results, limit=limit)


if __name__ == '__main__':
    main()
