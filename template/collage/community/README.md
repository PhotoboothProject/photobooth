# Custom collage designes

Custom collage designes of the community can be contributed here.

## Required Metadata Fields

| Field | Description | Type |
|-------|-------------|------|
| name | Display name for the layout in the selection UI | String |
| author | Name of the creator or source of the layout | String |
| aspect_ratio | Intended aspect ratio of the canvas or individual photo slots | String (format: "W:H") |
| width | Width of the canvas in pixels | Number |
| height | Height of the canvas in pixels | Number |

---

## Required Layout Field

- **Field Name:** `layout`
- **Type:** Array of arrays
- **Purpose:** Defines each photo slot on the canvas.

### Layout Entry Structure

Each inner array must contain **five or six elements**:

| Index | Name | Description | Type |
|-------|------|-------------|------|
| 0 | horizontal position | Horizontal offset from the left edge of the canvas | Formula or Number |
| 1 | vertical position | Vertical offset from the top edge of the canvas | Formula or Number |
| 2 | width | Width of the photo slot | Formula or Number |
| 3 | height | Height of the photo slot | Formula or Number |
| 4 | rotation | Rotation angle of the photo slot in **degrees**. **Must be an integer.** | Integer |
| 5 | apply frame (optional) | Indicates whether a frame should be applied | Boolean (true/false) |

### Notes on Layout

- Each entry represents a **single photo slot**.
- Number of entries depends on the design (e.g., 4 slots for a 2×2 grid).
- Positions and sizes can be **absolute pixels** or **relative formulas** based on canvas width/height.
- **Rotation is required** and must always be provided as an integer.
- The **apply frame** field is optional; omit to respect defined Photobooth configuration.

---

## General Notes

- Ensure consistency between canvas dimensions and aspect ratio of individual slots.
- The layout structure should be maintained to allow automated rendering.
- All numeric fields should use the same units (pixels) or clearly defined relative formulas.
