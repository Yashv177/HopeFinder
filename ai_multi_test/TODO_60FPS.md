# 60 FPS Face Recognition Optimization - TODO

## ✅ PLAN APPROVED
**Target: 60 FPS smooth real-time matching (GPU accelerated)**

## 📋 IMPLEMENTATION STEPS

### Phase 1: GPU Setup & Remove I/O [PROGRESS: 1/4] ✅
- [x] 1.1 Detect GPU & configure DeepFace CUDA
- [ ] 1.2 Pre-build GPU model once  
- [ ] 1.3 Remove temp file I/O → direct numpy arrays
- [ ] 1.4 Add ONNX Runtime support

### Phase 2: Smart Processing [PROGRESS: 0/4]
- [ ] 2.1 Haar Cascade pre-filter (10ms detection)
- [ ] 2.2 Motion detection (skip 80% frames)
- [ ] 2.3 ROI-only processing (face crops)
- [ ] 2.4 Early match termination

### Phase 3: Multi-threading [PROGRESS: 0/3]
- [ ] 3.1 Separate capture/display/processing threads
- [ ] 3.2 Frame queue pipeline
- [ ] 3.3 Bounded queue (memory safe)

### Phase 4: Testing & Polish [PROGRESS: 0/3]
- [ ] 4.1 Verify 60 FPS benchmark
- [ ] 4.2 Scale test (100+ targets)
- [ ] 4.3 CPU fallback + error handling

## 🎯 METRICS TO TRACK
```
FPS Display: 60
Processing: <16ms/frame  
GPU Usage: 20-40%
CPU Usage: <10%
Memory: Stable
```

**Next: Start Phase 1 → GPU acceleration**
