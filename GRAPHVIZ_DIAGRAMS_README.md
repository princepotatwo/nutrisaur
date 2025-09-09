# WHO Growth Standards Decision Tree - Graphviz Diagrams

This directory contains three Graphviz diagram files that visualize the WHO Growth Standards decision tree algorithm:

## Files

1. **`who_growth_standards_decision_tree.dot`** - Complete overview diagram
2. **`who_growth_detailed_decision_tree.dot`** - Detailed decision flow diagram
3. **`who_growth_simple_decision_tree.dot`** - Simplified process flow diagram

## How to Generate Diagrams

### Method 1: Using Graphviz Command Line

```bash
# Install Graphviz (if not already installed)
# On Ubuntu/Debian:
sudo apt-get install graphviz

# On macOS:
brew install graphviz

# On Windows:
# Download from https://graphviz.org/download/

# Generate PNG images
dot -Tpng who_growth_standards_decision_tree.dot -o who_growth_standards_decision_tree.png
dot -Tpng who_growth_detailed_decision_tree.dot -o who_growth_detailed_decision_tree.png
dot -Tpng who_growth_simple_decision_tree.dot -o who_growth_simple_decision_tree.png

# Generate SVG images (scalable)
dot -Tsvg who_growth_standards_decision_tree.dot -o who_growth_standards_decision_tree.svg
dot -Tsvg who_growth_detailed_decision_tree.dot -o who_growth_detailed_decision_tree.svg
dot -Tsvg who_growth_simple_decision_tree.dot -o who_growth_simple_decision_tree.svg

# Generate PDF images
dot -Tpdf who_growth_standards_decision_tree.dot -o who_growth_standards_decision_tree.pdf
dot -Tpdf who_growth_detailed_decision_tree.dot -o who_growth_detailed_decision_tree.pdf
dot -Tpdf who_growth_simple_decision_tree.dot -o who_growth_simple_decision_tree.pdf
```

### Method 2: Using Online Graphviz Tools

1. Go to [Graphviz Online](https://dreampuf.github.io/GraphvizOnline/)
2. Copy and paste the contents of any `.dot` file
3. The diagram will be generated automatically
4. You can download the result as PNG, SVG, or PDF

### Method 3: Using VS Code Extension

1. Install the "Graphviz (dot) language support" extension in VS Code
2. Open any `.dot` file
3. Right-click and select "Open Preview to the Side"
4. The diagram will be rendered in the preview pane

## Diagram Descriptions

### 1. Complete Overview Diagram (`who_growth_standards_decision_tree.dot`)

**Purpose**: Shows the complete flow from input to output with all major components

**Key Features**:
- Input validation process
- All 5 growth indicators (WFA, HFA, WFH, WFL, BFA)
- Z-score calculations for each indicator
- Classification logic for each indicator
- Risk assessment process
- Recommendation generation
- Database storage
- Error handling

**Best for**: Understanding the complete system architecture

### 2. Detailed Decision Flow Diagram (`who_growth_detailed_decision_tree.dot`)

**Purpose**: Shows detailed decision points and branching logic

**Key Features**:
- Step-by-step validation process
- Individual data lookup for each indicator
- Detailed Z-score calculation process
- Binary decision trees for each classification
- Comprehensive risk assessment logic
- Error handling and retry mechanisms

**Best for**: Understanding the detailed decision logic and debugging

### 3. Simplified Process Flow Diagram (`who_growth_simple_decision_tree.dot`)

**Purpose**: Shows the high-level process flow in a clean, easy-to-understand format

**Key Features**:
- Simplified validation process
- Grouped processing steps
- Clear flow from input to output
- Minimal decision points
- Easy to follow for non-technical stakeholders

**Best for**: Presentations and high-level understanding

## Color Coding

The diagrams use a consistent color scheme:

- **Blue (#e1f5fe)**: Start/End nodes
- **Orange (#fff3e0)**: Input validation
- **Purple (#f3e5f5)**: Calculations
- **Green (#e8f5e8)**: Data processing and output
- **Yellow (#fff9c4)**: Z-score calculations
- **Red (#ffebee)**: Classification logic
- **Light Blue (#e3f2fd)**: Risk assessment
- **Light Green (#f1f8e9)**: Recommendations
- **Pink (#fce4ec)**: Database operations
- **Red (#ffcdd2)**: Error handling

## Customization

You can modify the diagrams by editing the `.dot` files:

### Adding New Nodes
```dot
newNode [label="New Node Label", fillcolor="#color", shape=box];
```

### Adding New Connections
```dot
existingNode -> newNode [label="Connection Label"];
```

### Changing Colors
```dot
node [fillcolor="#newcolor"];
```

### Changing Shapes
```dot
node [shape=ellipse];  // or box, diamond, circle, etc.
```

## Troubleshooting

### Common Issues

1. **"dot: command not found"**
   - Install Graphviz using the methods above

2. **"Syntax error in graph"**
   - Check for missing semicolons or brackets
   - Ensure all node names are unique
   - Verify all connections reference existing nodes

3. **"Output file not created"**
   - Check file permissions
   - Ensure the output directory exists
   - Try a different output format (PNG, SVG, PDF)

### Getting Help

- [Graphviz Documentation](https://graphviz.org/documentation/)
- [Graphviz Gallery](https://graphviz.org/gallery/)
- [DOT Language Reference](https://graphviz.org/doc/info/lang.html)

## Integration with Documentation

These diagrams can be integrated into your documentation by:

1. Generating the images using the methods above
2. Including them in your README files
3. Using them in presentations
4. Embedding them in web pages
5. Including them in technical specifications

The diagrams provide a visual representation of the complex decision tree logic, making it easier for developers, healthcare professionals, and stakeholders to understand the WHO Growth Standards implementation.
